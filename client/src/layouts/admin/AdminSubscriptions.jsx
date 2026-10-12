import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const STATUS_BADGE = {
  active:   'bg-ds-primary/10 text-ds-primary',
  pending:  'bg-ds-tertiary-fixed text-ds-tertiary',
  expired:  'bg-ds-error-container text-ds-error',
  canceled: 'bg-[#e1e3e4] text-ds-outline',
  rejected: 'bg-ds-error-container text-ds-error',
};

const PACKAGE_BG   = ['bg-ds-tertiary-fixed', 'bg-ds-secondary-container', 'bg-ds-primary-fixed/30', 'bg-[#e1e3e4]', 'bg-ds-error-container'];
const PACKAGE_ICON = ['diamond', 'star', 'workspace_premium', 'bolt', 'local_fire_department'];
const PACKAGE_IC   = ['text-ds-tertiary', 'text-ds-secondary', 'text-ds-primary', 'text-ds-on-surface-variant', 'text-ds-error'];

const STATUSES = ['', 'active', 'pending', 'expired', 'canceled', 'rejected'];

export default function AdminSubscriptions() {
  const [tab, setTab] = useState('subscriptions'); // 'subscriptions' | 'packages'
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const params = new URLSearchParams({ page });
  if (search) params.set('search', search);
  if (status) params.set('status', status);

  const { data: subData, isLoading: subLoading } = useQuery({
    queryKey: ['admin-subscriptions', search, status, page],
    queryFn: () => api.get(`/admin/subscriptions?${params}`).then(r => r.data),
    enabled: tab === 'subscriptions',
    keepPreviousData: true,
  });

  const { data: pkgData = [], isLoading: pkgLoading } = useQuery({
    queryKey: ['admin-packages'],
    queryFn: () => api.get('/admin/packages').then(r => r.data),
    enabled: tab === 'packages',
  });

  const subs = subData?.data ?? [];
  const meta = subData ?? {};

  const qc = useQueryClient();
  const { mutate: updateStatus, variables: mutVars, isPending: mutLoading } = useMutation({
    mutationFn: ({ id, status }) =>
      api.patch(`/admin/subscriptions/${id}/status`, { status }).then(r => r.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-subscriptions'] }),
  });

  return (
    <div className="space-y-6">
      <h1 className="font-headline text-2xl font-bold text-ds-primary">Subscriptions</h1>

      {/* Tabs */}
      <div className="flex gap-1 bg-ds-surface-container-low p-1 rounded-full w-fit">
        {[{ key: 'subscriptions', label: 'User Subscriptions' }, { key: 'packages', label: 'Packages' }].map(t => (
          <button key={t.key} onClick={() => setTab(t.key)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all ${
              tab === t.key ? 'bg-white text-ds-primary shadow-xs' : 'text-ds-outline hover:text-ds-on-surface-variant'
            }`}>{t.label}</button>
        ))}
      </div>

      {tab === 'subscriptions' && (
        <>
          {/* Filters */}
          <div className="bg-white rounded-lg p-4 flex flex-wrap gap-3">
            <div className="relative flex-1 min-w-48">
              <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-ds-outline text-base">search</span>
              <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
                placeholder="Search by user name or email…"
                className="w-full bg-ds-surface-container-low border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20" />
            </div>
            <select value={status} onChange={e => { setStatus(e.target.value); setPage(1); }}
              className="bg-ds-surface-container-low border-none rounded-full py-2 px-4 text-sm font-medium text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20">
              {STATUSES.map(s => <option key={s} value={s}>{s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All Statuses'}</option>)}
            </select>
          </div>

          <div className="bg-white rounded-lg overflow-hidden">
            {subLoading ? (
              <div className="flex items-center justify-center h-48">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
              </div>
            ) : subs.length === 0 ? (
              <div className="text-center py-16 text-ds-outline">
                <span className="material-symbols-outlined text-4xl block mb-2">card_membership</span>
                No subscriptions found
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-[#e1e3e4] bg-ds-surface-container-low">
                      {['User', 'Package', 'Amount Paid', 'Status', 'Started', 'Expires', 'Actions'].map(h => (
                        <th key={h} className="text-left px-5 py-3 text-xs font-bold text-ds-outline uppercase tracking-widest whitespace-nowrap">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {subs.map(s => (
                      <tr key={s.id} className="border-b border-ds-surface-container-low hover:bg-[#f8f9fa] transition-colors">
                        <td className="px-5 py-4">
                          <p className="font-semibold text-[#191c1d]">{s.user?.name ?? '—'}</p>
                          <p className="text-xs text-ds-outline">{s.user?.email}</p>
                        </td>
                        <td className="px-5 py-4 font-medium text-ds-on-surface-variant">{s.package?.name ?? '—'}</td>
                        <td className="px-5 py-4 font-bold text-ds-primary">
                          LKR {Number(s.amount_paid ?? 0).toLocaleString()}
                        </td>
                        <td className="px-5 py-4">
                          <div className="flex flex-wrap gap-1.5 items-center">
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_BADGE[s.status] ?? 'bg-[#e1e3e4] text-ds-on-surface-variant'}`}>
                              {s.status}
                            </span>
                            {s.is_trial && (
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#f4a261]/20 text-[#8a4b15] border border-[#f4a261]/30">
                                Trial
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-5 py-4 text-xs text-ds-outline whitespace-nowrap">
                          {s.starts_at ? new Date(s.starts_at).toLocaleDateString() : '—'}
                        </td>
                        <td className="px-5 py-4 text-xs whitespace-nowrap">
                          {s.ends_at ? (
                            <span className={new Date(s.ends_at) < new Date() ? 'text-ds-error font-semibold' : 'text-ds-on-surface-variant'}>
                              {new Date(s.ends_at).toLocaleDateString()}
                            </span>
                          ) : '—'}
                        </td>
                        <td className="px-5 py-4 whitespace-nowrap">
                          {(() => {
                            const busy = mutLoading && mutVars?.id === s.id;
                            if (s.status === 'pending') return (
                              <div className="flex gap-2">
                                <button
                                  onClick={() => updateStatus({ id: s.id, status: 'active' })}
                                  disabled={busy}
                                  className="px-3 py-1 rounded-full text-xs font-semibold bg-ds-primary/10 text-ds-primary hover:bg-ds-primary/20 disabled:opacity-50 transition-colors">
                                  Approve
                                </button>
                                <button
                                  onClick={() => updateStatus({ id: s.id, status: 'rejected' })}
                                  disabled={busy}
                                  className="px-3 py-1 rounded-full text-xs font-semibold bg-ds-error-container text-ds-error hover:bg-ds-error-container/80 disabled:opacity-50 transition-colors">
                                  Reject
                                </button>
                              </div>
                            );
                            if (s.status === 'active') return (
                              <button
                                onClick={() => updateStatus({ id: s.id, status: 'canceled' })}
                                disabled={busy}
                                className="px-3 py-1 rounded-full text-xs font-semibold bg-[#e1e3e4] text-ds-outline hover:bg-ds-outline-variant/50 disabled:opacity-50 transition-colors">
                                Cancel
                              </button>
                            );
                            return (
                              <button
                                onClick={() => updateStatus({ id: s.id, status: 'active' })}
                                disabled={busy}
                                className="px-3 py-1 rounded-full text-xs font-semibold bg-ds-primary/10 text-ds-primary hover:bg-ds-primary/20 disabled:opacity-50 transition-colors">
                                Activate
                              </button>
                            );
                          })()}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {meta.last_page > 1 && (
              <div className="flex items-center justify-between px-5 py-4 border-t border-[#e1e3e4]">
                <p className="text-xs text-ds-outline">Page {meta.current_page} of {meta.last_page} · {meta.total} results</p>
                <div className="flex gap-2">
                  <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={meta.current_page <= 1}
                    className="px-3 py-1.5 rounded-lg bg-ds-surface-container-low text-ds-on-surface-variant text-xs font-semibold disabled:opacity-40 hover:bg-[#e1e3e4] transition-colors">Prev</button>
                  <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={meta.current_page >= meta.last_page}
                    className="px-3 py-1.5 rounded-lg bg-ds-primary text-white text-xs font-semibold disabled:opacity-40 hover:bg-ds-primary-container transition-colors">Next</button>
                </div>
              </div>
            )}
          </div>
        </>
      )}

      {tab === 'packages' && (
        <>
          {pkgLoading ? (
            <div className="flex items-center justify-center h-48">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
              {pkgData.map((pkg, i) => (
                <div key={pkg.id} className="bg-white rounded-lg p-6 relative overflow-hidden">
                  {pkg.is_popular && (
                    <span className="absolute top-3 right-3 text-[10px] bg-ds-primary text-white px-2 py-0.5 rounded-full font-bold uppercase tracking-widest">Popular</span>
                  )}
                  <div className={`w-14 h-14 rounded-xl ${PACKAGE_BG[i % PACKAGE_BG.length]} flex items-center justify-center mb-4`}>
                    <span className={`material-symbols-outlined text-2xl ${PACKAGE_IC[i % PACKAGE_IC.length]}`}>
                      {PACKAGE_ICON[i % PACKAGE_ICON.length]}
                    </span>
                  </div>
                  <h3 className="font-headline font-bold text-lg text-[#191c1d]">{pkg.name}</h3>
                  <p className="font-headline text-2xl font-extrabold text-ds-primary mt-1">
                    LKR {Number(pkg.price).toLocaleString()}
                    <span className="text-sm font-normal text-ds-outline"> / {pkg.duration_days}d</span>
                  </p>

                  <div className="mt-4 space-y-2 text-sm">
                    {[
                      { icon: 'store',        label: `${pkg.max_salons ?? '∞'} salon${pkg.max_salons !== 1 ? 's' : ''}` },
                      { icon: 'group',        label: `${pkg.max_staff ?? '∞'} staff` },
                      { icon: 'content_cut',  label: `${pkg.max_services ?? '∞'} services` },
                      { icon: 'calendar_month', label: `${pkg.max_bookings_per_month ?? '∞'} bookings/mo` },
                    ].map(feat => (
                      <div key={feat.label} className="flex items-center gap-2 text-ds-on-surface-variant">
                        <span className="material-symbols-outlined text-sm text-ds-outline">{feat.icon}</span>
                        {feat.label}
                      </div>
                    ))}
                    {pkg.analytics && (
                      <div className="flex items-center gap-2 text-ds-primary font-semibold">
                        <span className="material-symbols-outlined text-sm">bar_chart</span>Analytics
                      </div>
                    )}
                    {pkg.priority_support && (
                      <div className="flex items-center gap-2 text-ds-primary font-semibold">
                        <span className="material-symbols-outlined text-sm">support_agent</span>Priority support
                      </div>
                    )}
                  </div>

                  <div className="mt-5 pt-4 border-t border-[#e1e3e4] flex items-center justify-between">
                    <div>
                      <p className="font-headline text-xl font-extrabold text-ds-primary">{pkg.active_count ?? 0}</p>
                      <p className="text-[10px] text-ds-outline uppercase tracking-widest font-semibold">Active users</p>
                    </div>
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${pkg.is_active ? 'bg-ds-primary/10 text-ds-primary' : 'bg-[#e1e3e4] text-ds-outline'}`}>
                      {pkg.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}
    </div>
  );
}
