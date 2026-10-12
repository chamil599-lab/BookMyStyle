import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const STATUS_BADGE = {
  pending:   { cls: 'bg-ds-tertiary-fixed text-ds-tertiary',   icon: 'schedule' },
  approved:  { cls: 'bg-ds-primary/10 text-ds-primary', icon: 'check_circle' },
  completed: { cls: 'bg-ds-secondary/10 text-ds-secondary', icon: 'task_alt' },
  rejected:  { cls: 'bg-ds-error-container text-ds-error',   icon: 'cancel' },
};

function StatusBadge({ status }) {
  const cfg = STATUS_BADGE[status] ?? STATUS_BADGE.pending;
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${cfg.cls}`}>
      <span className="material-symbols-outlined text-sm" style={{ fontSize: 14 }}>{cfg.icon}</span>
      {status}
    </span>
  );
}

const STATUSES = ['', 'pending', 'approved', 'completed', 'rejected'];

export default function AdminBookings() {
  const qc = useQueryClient();
  const [search, setSearch]   = useState('');
  const [status, setStatus]   = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo]   = useState('');
  const [page, setPage]       = useState(1);
  const [actionId, setActionId] = useState(null);

  const params = new URLSearchParams({ page });
  if (search)   params.set('search',    search);
  if (status)   params.set('status',    status);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo)   params.set('date_to',   dateTo);

  const { data, isLoading } = useQuery({
    queryKey: ['admin-bookings', search, status, dateFrom, dateTo, page],
    queryFn: () => api.get(`/admin/bookings?${params}`).then(r => r.data),
    keepPreviousData: true,
  });

  const mutation = useMutation({
    mutationFn: ({ id, status }) => api.patch(`/admin/bookings/${id}/status`, { status }),
    onSuccess: () => {
      qc.invalidateQueries(['admin-bookings']);
      setActionId(null);
    },
  });

  const bookings = data?.data ?? [];
  const meta = data ?? {};

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="font-headline text-2xl font-bold text-ds-primary">Bookings</h1>
        <span className="text-sm text-ds-outline">{meta.total ?? 0} total</span>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg p-4 flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-48">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-ds-outline text-base">search</span>
          <input
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search name, email, phone, salon…"
            className="w-full bg-ds-surface-container-low border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20"
          />
        </div>
        <select
          value={status}
          onChange={e => { setStatus(e.target.value); setPage(1); }}
          className="bg-ds-surface-container-low border-none rounded-full py-2 px-4 text-sm font-medium text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20"
        >
          {STATUSES.map(s => <option key={s} value={s}>{s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All Statuses'}</option>)}
        </select>
        <input type="date" value={dateFrom} onChange={e => { setDateFrom(e.target.value); setPage(1); }}
          className="bg-ds-surface-container-low border-none rounded-full py-2 px-4 text-sm text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20" />
        <input type="date" value={dateTo} onChange={e => { setDateTo(e.target.value); setPage(1); }}
          className="bg-ds-surface-container-low border-none rounded-full py-2 px-4 text-sm text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20" />
        {(search || status || dateFrom || dateTo) && (
          <button onClick={() => { setSearch(''); setStatus(''); setDateFrom(''); setDateTo(''); setPage(1); }}
            className="text-xs text-ds-error font-semibold px-3 hover:underline">Clear</button>
        )}
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
          </div>
        ) : bookings.length === 0 ? (
          <div className="text-center py-16 text-ds-outline">
            <span className="material-symbols-outlined text-4xl block mb-2">calendar_month</span>
            No bookings found
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-ds-surface-container-low">
                  {['Customer', 'Salon', 'Service', 'Date & Time', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-ds-outline uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {bookings.map(b => (
                  <tr key={b.id} className="border-b border-ds-surface-container-low hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4">
                      <p className="font-semibold text-[#191c1d]">{b.name}</p>
                      <p className="text-xs text-ds-outline">{b.email}</p>
                      {b.phone && <p className="text-xs text-ds-outline">{b.phone}</p>}
                    </td>
                    <td className="px-5 py-4 text-ds-on-surface-variant whitespace-nowrap">{b.salon?.name ?? '—'}</td>
                    <td className="px-5 py-4">
                      <p className="text-ds-on-surface-variant">{b.service?.title ?? '—'}</p>
                      {b.service?.price && <p className="text-xs text-ds-outline">LKR {Number(b.service.price).toLocaleString()}</p>}
                    </td>
                    <td className="px-5 py-4 whitespace-nowrap">
                      <p className="font-medium text-[#191c1d]">{b.date}</p>
                      <p className="text-xs text-ds-outline">{b.time}</p>
                    </td>
                    <td className="px-5 py-4"><StatusBadge status={b.status} /></td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1">
                        {b.status !== 'approved' && b.status !== 'completed' && (
                          <button
                            onClick={() => mutation.mutate({ id: b.id, status: 'approved' })}
                            disabled={mutation.isPending && actionId === b.id}
                            className="p-1.5 rounded-lg bg-ds-primary/10 text-ds-primary hover:bg-ds-primary/20 transition-colors"
                            title="Approve"
                          >
                            <span className="material-symbols-outlined text-base">check</span>
                          </button>
                        )}
                        {b.status !== 'rejected' && b.status !== 'completed' && (
                          <button
                            onClick={() => { setActionId(b.id); mutation.mutate({ id: b.id, status: 'rejected' }); }}
                            disabled={mutation.isPending && actionId === b.id}
                            className="p-1.5 rounded-lg bg-ds-error-container text-ds-error hover:bg-ds-error/20 transition-colors"
                            title="Reject"
                          >
                            <span className="material-symbols-outlined text-base">close</span>
                          </button>
                        )}
                        {b.status === 'approved' && (
                          <button
                            onClick={() => mutation.mutate({ id: b.id, status: 'completed' })}
                            className="p-1.5 rounded-lg bg-ds-secondary/10 text-ds-secondary hover:bg-ds-secondary/20 transition-colors"
                            title="Mark Completed"
                          >
                            <span className="material-symbols-outlined text-base">task_alt</span>
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta.last_page > 1 && (
          <div className="flex items-center justify-between px-5 py-4 border-t border-[#e1e3e4]">
            <p className="text-xs text-ds-outline">
              Page {meta.current_page} of {meta.last_page} · {meta.total} results
            </p>
            <div className="flex gap-2">
              <button
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={meta.current_page <= 1}
                className="px-3 py-1.5 rounded-lg bg-ds-surface-container-low text-ds-on-surface-variant text-xs font-semibold disabled:opacity-40 hover:bg-[#e1e3e4] transition-colors"
              >Prev</button>
              <button
                onClick={() => setPage(p => Math.min(meta.last_page, p + 1))}
                disabled={meta.current_page >= meta.last_page}
                className="px-3 py-1.5 rounded-lg bg-ds-primary text-white text-xs font-semibold disabled:opacity-40 hover:bg-ds-primary-container transition-colors"
              >Next</button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
