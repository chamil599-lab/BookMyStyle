import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const STATUS_BADGE = {
  active:   'bg-[#004242]/10 text-[#004242]',
  pending:  'bg-[#ffdbca] text-[#5d2e10]',
  expired:  'bg-[#ffdad6] text-[#ba1a1a]',
  canceled: 'bg-[#e1e3e4] text-[#6f7979]',
  rejected: 'bg-[#ffdad6] text-[#ba1a1a]',
};

const PACKAGE_BG   = ['bg-[#ffdbca]', 'bg-[#c7e6e5]', 'bg-[#a8efef]/30', 'bg-[#e1e3e4]', 'bg-[#ffdad6]'];
const PACKAGE_ICON = ['diamond', 'star', 'workspace_premium', 'bolt', 'local_fire_department'];
const PACKAGE_IC   = ['text-[#5d2e10]', 'text-[#476363]', 'text-[#004242]', 'text-[#3f4948]', 'text-[#ba1a1a]'];

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
      <h1 className="font-headline text-2xl font-bold text-[#004242]">Subscriptions</h1>

      {/* Tabs */}
      <div className="flex gap-1 bg-[#f3f4f5] p-1 rounded-full w-fit">
        {[{ key: 'subscriptions', label: 'User Subscriptions' }, { key: 'packages', label: 'Packages' }].map(t => (
          <button key={t.key} onClick={() => setTab(t.key)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all ${
              tab === t.key ? 'bg-white text-[#004242] shadow-sm' : 'text-[#6f7979] hover:text-[#3f4948]'
            }`}>{t.label}</button>
        ))}
      </div>

      {tab === 'subscriptions' && (
        <>
          {/* Filters */}
          <div className="bg-white rounded-[0.5rem] p-4 flex flex-wrap gap-3">
            <div className="relative flex-1 min-w-48">
              <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
              <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
                placeholder="Search by user name or email…"
                className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
            </div>
            <select value={status} onChange={e => { setStatus(e.target.value); setPage(1); }}
              className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm font-medium text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20">
              {STATUSES.map(s => <option key={s} value={s}>{s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All Statuses'}</option>)}
            </select>
          </div>

          <div className="bg-white rounded-[0.5rem] overflow-hidden">
            {subLoading ? (
              <div className="flex items-center justify-center h-48">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
              </div>
            ) : subs.length === 0 ? (
              <div className="text-center py-16 text-[#6f7979]">
                <span className="material-symbols-outlined text-4xl block mb-2">card_membership</span>
                No subscriptions found
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-[#e1e3e4] bg-[#f3f4f5]">
                      {['User', 'Package', 'Amount Paid', 'Status', 'Started', 'Expires', 'Actions'].map(h => (
                        <th key={h} className="text-left px-5 py-3 text-xs font-bold text-[#6f7979] uppercase tracking-widest whitespace-nowrap">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {subs.map(s => (
                      <tr key={s.id} className="border-b border-[#f3f4f5] hover:bg-[#f8f9fa] transition-colors">
                        <td className="px-5 py-4">
                          <p className="font-semibold text-[#191c1d]">{s.user?.name ?? '—'}</p>
                          <p className="text-xs text-[#6f7979]">{s.user?.email}</p>
                        </td>
                        <td className="px-5 py-4 font-medium text-[#3f4948]">{s.package?.name ?? '—'}</td>
                        <td className="px-5 py-4 font-bold text-[#004242]">
                          LKR {Number(s.amount_paid ?? 0).toLocaleString()}
                        </td>
                        <td className="px-5 py-4">
                          <div className="flex flex-wrap gap-1.5 items-center">
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_BADGE[s.status] ?? 'bg-[#e1e3e4] text-[#3f4948]'}`}>
                              {s.status}
                            </span>
                            {s.is_trial && (
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#f4a261]/20 text-[#8a4b15] border border-[#f4a261]/30">
                                Trial
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-5 py-4 text-xs text-[#6f7979] whitespace-nowrap">
                          {s.starts_at ? new Date(s.starts_at).toLocaleDateString() : '—'}
                        </td>
                        <td className="px-5 py-4 text-xs whitespace-nowrap">
                          {s.ends_at ? (
                            <span className={new Date(s.ends_at) < new Date() ? 'text-[#ba1a1a] font-semibold' : 'text-[#3f4948]'}>
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
                                  className="px-3 py-1 rounded-full text-xs font-semibold bg-[#004242]/10 text-[#004242] hover:bg-[#004242]/20 disabled:opacity-50 transition-colors">
                                  Approve
                                </button>
                                <button
                                  onClick={() => updateStatus({ id: s.id, status: 'rejected' })}
                                  disabled={busy}
                                  className="px-3 py-1 rounded-full text-xs font-semibold bg-[#ffdad6] text-[#ba1a1a] hover:bg-[#ffdad6]/80 disabled:opacity-50 transition-colors">
                                  Reject
                                </button>
                              </div>
                            );
                            if (s.status === 'active') return (
                              <button
                                onClick={() => updateStatus({ id: s.id, status: 'canceled' })}
                                disabled={busy}
                                className="px-3 py-1 rounded-full text-xs font-semibold bg-[#e1e3e4] text-[#6f7979] hover:bg-[#bec8c8]/50 disabled:opacity-50 transition-colors">
                                Cancel
                              </button>
                            );
                            return (
                              <button
                                onClick={() => updateStatus({ id: s.id, status: 'active' })}
                                disabled={busy}
                                className="px-3 py-1 rounded-full text-xs font-semibold bg-[#004242]/10 text-[#004242] hover:bg-[#004242]/20 disabled:opacity-50 transition-colors">
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
                <p className="text-xs text-[#6f7979]">Page {meta.current_page} of {meta.last_page} · {meta.total} results</p>
                <div className="flex gap-2">
                  <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={meta.current_page <= 1}
                    className="px-3 py-1.5 rounded-lg bg-[#f3f4f5] text-[#3f4948] text-xs font-semibold disabled:opacity-40 hover:bg-[#e1e3e4] transition-colors">Prev</button>
                  <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={meta.current_page >= meta.last_page}
                    className="px-3 py-1.5 rounded-lg bg-[#004242] text-white text-xs font-semibold disabled:opacity-40 hover:bg-[#005b5c] transition-colors">Next</button>
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
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
              {pkgData.map((pkg, i) => (
                <div key={pkg.id} className="bg-white rounded-[0.5rem] p-6 relative overflow-hidden">
                  {pkg.is_popular && (
                    <span className="absolute top-3 right-3 text-[10px] bg-[#004242] text-white px-2 py-0.5 rounded-full font-bold uppercase tracking-widest">Popular</span>
                  )}
                  <div className={`w-14 h-14 rounded-xl ${PACKAGE_BG[i % PACKAGE_BG.length]} flex items-center justify-center mb-4`}>
                    <span className={`material-symbols-outlined text-2xl ${PACKAGE_IC[i % PACKAGE_IC.length]}`}>
                      {PACKAGE_ICON[i % PACKAGE_ICON.length]}
                    </span>
                  </div>
                  <h3 className="font-headline font-bold text-lg text-[#191c1d]">{pkg.name}</h3>
                  <p className="font-headline text-2xl font-extrabold text-[#004242] mt-1">
                    LKR {Number(pkg.price).toLocaleString()}
                    <span className="text-sm font-normal text-[#6f7979]"> / {pkg.duration_days}d</span>
                  </p>

                  <div className="mt-4 space-y-2 text-sm">
                    {[
                      { icon: 'store',        label: `${pkg.max_salons ?? '∞'} salon${pkg.max_salons !== 1 ? 's' : ''}` },
                      { icon: 'group',        label: `${pkg.max_staff ?? '∞'} staff` },
                      { icon: 'content_cut',  label: `${pkg.max_services ?? '∞'} services` },
                      { icon: 'calendar_month', label: `${pkg.max_bookings_per_month ?? '∞'} bookings/mo` },
                    ].map(feat => (
                      <div key={feat.label} className="flex items-center gap-2 text-[#3f4948]">
                        <span className="material-symbols-outlined text-sm text-[#6f7979]">{feat.icon}</span>
                        {feat.label}
                      </div>
                    ))}
                    {pkg.analytics && (
                      <div className="flex items-center gap-2 text-[#004242] font-semibold">
                        <span className="material-symbols-outlined text-sm">bar_chart</span>Analytics
                      </div>
                    )}
                    {pkg.priority_support && (
                      <div className="flex items-center gap-2 text-[#004242] font-semibold">
                        <span className="material-symbols-outlined text-sm">support_agent</span>Priority support
                      </div>
                    )}
                  </div>

                  <div className="mt-5 pt-4 border-t border-[#e1e3e4] flex items-center justify-between">
                    <div>
                      <p className="font-headline text-xl font-extrabold text-[#004242]">{pkg.active_count ?? 0}</p>
                      <p className="text-[10px] text-[#6f7979] uppercase tracking-widest font-semibold">Active users</p>
                    </div>
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${pkg.is_active ? 'bg-[#004242]/10 text-[#004242]' : 'bg-[#e1e3e4] text-[#6f7979]'}`}>
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
