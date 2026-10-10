import { createContext, useCallback, useContext, useState } from 'react';

const ToastContext = createContext({ showToast: () => {} });

export const useToast = () => useContext(ToastContext);

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const dismiss = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const showToast = useCallback((toast) => {
    const id = Math.random().toString(36).slice(2);
    const entry = {
      id,
      title: toast.title || 'Notification',
      message: toast.message || '',
      duration: toast.duration ?? 5000,
    };
    setToasts((prev) => [...prev, entry]);
    if (entry.duration > 0) {
      setTimeout(() => dismiss(id), entry.duration);
    }
  }, [dismiss]);

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <div className="pointer-events-none fixed top-20 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full">
        {toasts.map((t) => (
          <div
            key={t.id}
            className="pointer-events-auto flex items-start gap-3 rounded-2xl border border-[#004242]/10 bg-white px-4 py-3 shadow-[0_12px_32px_rgba(0,66,66,0.18)] animate-[slideIn_0.25s_ease-out]"
            style={{ animation: 'slideIn 0.25s ease-out' }}
          >
            <span className="material-symbols-outlined text-[#004242] mt-0.5">notifications_active</span>
            <div className="flex-1 min-w-0">
              <div className="text-sm font-bold text-[#004242] truncate">{t.title}</div>
              {t.message && (
                <div className="text-xs text-[#3a4a4a] mt-0.5 line-clamp-3">{t.message}</div>
              )}
            </div>
            <button
              onClick={() => dismiss(t.id)}
              className="text-[#6f7979] hover:text-[#004242]"
              aria-label="Dismiss"
            >
              <span className="material-symbols-outlined text-base">close</span>
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}
