import { useEffect, useRef } from 'react';
import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';
import api from '../utils/api';
import { useToast } from '../context/ToastContext';

const NOTIFICATIONS_KEY = ['notifications'];

function apiUrl(path) {
  const baseURL = api.defaults.baseURL || '/api';

  if (/^https?:\/\//i.test(baseURL)) {
    return `${baseURL.replace(/\/$/, '')}${path}`;
  }

  return `${baseURL.replace(/\/$/, '')}${path}`;
}

export function useNotifications({ enabled = true } = {}) {
  const queryClient = useQueryClient();
  const { showToast } = useToast();
  const seenIdsRef = useRef(null);

  const query = useQuery({
    queryKey: NOTIFICATIONS_KEY,
    enabled,
    queryFn: async () => {
      const { data } = await api.get('/notifications');
      return data;
    },
    refetchInterval: enabled ? 60000 : false,
    refetchIntervalInBackground: true,
    refetchOnWindowFocus: true,
    refetchOnMount: 'always',
    refetchOnReconnect: true,
    staleTime: 0,
    retry: 1,
  });

  const notifications = query.data?.notifications ?? [];
  const unreadCount = query.data?.unread_count ?? 0;

  useEffect(() => {
    if (!enabled) return;

    let cancelled = false;
    let reconnectTimer = null;
    let controller = null;

    const connect = async () => {
      controller = new AbortController();

      try {
        const token = localStorage.getItem('auth_token');
        if (!token) return;

        const response = await fetch(apiUrl('/notifications/stream'), {
          method: 'GET',
          headers: {
            Accept: 'text/event-stream',
            Authorization: `Bearer ${token}`,
            'X-Authorization': `Bearer ${token}`,
          },
          signal: controller.signal,
        });

        if (!response.ok || !response.body) {
          throw new Error('Notification stream unavailable');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (!cancelled) {
          const { value, done } = await reader.read();
          if (done) break;

          buffer += decoder.decode(value, { stream: true });
          const events = buffer.split('\n\n');
          buffer = events.pop() || '';

          events.forEach((eventText) => {
            const dataLine = eventText
              .split('\n')
              .find((line) => line.startsWith('data: '));

            if (!dataLine) return;

            try {
              const payload = JSON.parse(dataLine.slice(6));
              queryClient.setQueryData(NOTIFICATIONS_KEY, payload);
            } catch (error) {
              console.warn('Failed to parse notification stream event', error);
            }
          });
        }
        if (!cancelled) reconnectTimer = window.setTimeout(connect, 3000);
      } catch (error) {
        if (!cancelled && error.name !== 'AbortError') {
          reconnectTimer = window.setTimeout(connect, 3000);
        }
      }
    };

    connect();

    return () => {
      cancelled = true;
      if (reconnectTimer) window.clearTimeout(reconnectTimer);
      controller?.abort();
    };
  }, [enabled, queryClient]);

  // Detect newly arrived notifications and toast them
  useEffect(() => {
    if (!enabled) return;
    const ids = notifications.map((n) => n.id);
    const seen = seenIdsRef.current;

    if (seen === null) {
      // first load — establish baseline, no toast
      seenIdsRef.current = new Set(ids);
      return;
    }

    const fresh = notifications.filter(
      (n) => !seen.has(n.id) && !n.read_at
    );
    fresh.forEach((n) => {
      showToast({
        title: n.data?.title || 'Notification',
        message: n.data?.message || '',
      });
    });

    seenIdsRef.current = new Set(ids);
  }, [notifications, enabled, showToast]);

  const markAsReadMutation = useMutation({
    mutationFn: async (id) => {
      const { data } = await api.patch(`/notifications/${id}/read`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_KEY });
    },
  });

  const markAllAsReadMutation = useMutation({
    mutationFn: async () => {
      const { data } = await api.post('/notifications/read-all');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_KEY });
    },
  });

  const clearMutation = useMutation({
    mutationFn: async (id) => {
      const { data } = await api.delete(`/notifications/${id}`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_KEY });
    },
  });

  const clearAllMutation = useMutation({
    mutationFn: async () => {
      const { data } = await api.delete('/notifications');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_KEY });
    },
  });

  return {
    notifications,
    unreadCount,
    isLoading: query.isLoading,
    refetch: query.refetch,
    markAsRead: markAsReadMutation.mutate,
    markAllAsRead: markAllAsReadMutation.mutate,
    clear: clearMutation.mutate,
    clearAll: clearAllMutation.mutate,
  };
}
