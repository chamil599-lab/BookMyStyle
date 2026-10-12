import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useNotifications } from '../hooks/useNotifications';

function timeAgo(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;
  return date.toLocaleDateString();
}

export default function NotificationBell() {
  const [open, setOpen] = useState(false);
  const navigate = useNavigate();
  const containerRef = useRef(null);

  const { notifications, unreadCount, markAsRead, markAllAsRead, clear, clearAll } =
    useNotifications();

  useEffect(() => {
    if (!open) return;
    const onClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onClickOutside);
    return () => document.removeEventListener('mousedown', onClickOutside);
  }, [open]);

  const handleClick = (n) => {
    if (!n.read_at) markAsRead(n.id);
    if (n.data?.link) navigate(n.data.link);
    setOpen(false);
  };

  return (
    <div className="relative" ref={containerRef}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="relative p-2 text-ds-primary hover:bg-[#dff4f2] rounded-full transition-colors"
        aria-label="Notifications"
      >
        <span className="material-symbols-outlined text-xl">notifications</span>
        {unreadCount > 0 && (
          <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shadow-sm">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 mt-2 w-[360px] max-w-[90vw] rounded-2xl bg-white border border-ds-primary/10 shadow-[0_20px_50px_rgba(0,66,66,0.18)] overflow-hidden z-50">
          <div className="flex items-center justify-between px-4 py-3 border-b border-ds-primary/10">
            <span className="font-bold text-ds-primary text-sm">Notifications</span>
            <div className="flex items-center gap-3">
              <button
                onClick={() => {
                  markAllAsRead();
                  setOpen(false);
                }}
                disabled={unreadCount === 0}
                className="text-xs font-semibold text-ds-primary hover:underline disabled:text-[#9aa6a6] disabled:no-underline disabled:cursor-not-allowed"
              >
                Mark all as read
              </button>
              <button
                onClick={() => {
                  clearAll();
                  setOpen(false);
                }}
                disabled={notifications.length === 0}
                className="text-xs font-semibold text-red-500 hover:underline disabled:text-[#9aa6a6] disabled:no-underline disabled:cursor-not-allowed"
              >
                Clear all
              </button>
            </div>
          </div>

          <div className="max-h-[400px] overflow-y-auto">
            {notifications.length === 0 ? (
              <div className="px-4 py-8 text-center text-sm text-ds-outline">
                You have no notifications yet.
              </div>
            ) : (
              notifications.map((n) => (
                <div
                  key={n.id}
                  className={`group flex items-start gap-3 border-b border-ds-primary/5 last:border-b-0 transition-colors hover:bg-[#eef7f6] ${
                    !n.read_at ? 'bg-[#f4fbfa]' : ''
                  }`}
                >
                  <button
                    onClick={() => handleClick(n)}
                    className="flex-1 text-left px-4 py-3 flex items-start gap-3 min-w-0"
                  >
                    <span
                      className={`mt-1 h-2 w-2 rounded-full shrink-0 ${
                        !n.read_at ? 'bg-ds-primary' : 'bg-transparent'
                      }`}
                    />
                    <div className="flex-1 min-w-0">
                      <div className="text-sm font-semibold text-ds-primary truncate">
                        {n.data?.title || 'Notification'}
                      </div>
                      {n.data?.message && (
                        <div className="text-xs text-[#3a4a4a] mt-0.5 line-clamp-2">
                          {n.data.message}
                        </div>
                      )}
                      <div className="text-[10px] uppercase tracking-wider text-ds-outline mt-1">
                        {timeAgo(n.created_at)}
                      </div>
                    </div>
                  </button>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      clear(n.id);
                      setOpen(false);
                    }}
                    title="Clear"
                    aria-label="Clear notification"
                    className="shrink-0 p-2 mr-1 mt-1 text-ds-outline hover:text-red-500 hover:bg-red-50 rounded-full transition-colors opacity-60 group-hover:opacity-100"
                  >
                    <span className="material-symbols-outlined text-base">close</span>
                  </button>
                </div>
              ))
            )}
          </div>

        </div>
      )}
    </div>
  );
}
