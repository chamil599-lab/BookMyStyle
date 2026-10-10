import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const ROLE_BADGE = {
  admin:    'bg-[#ffdad6] text-[#ba1a1a]',
  owner:    'bg-[#004242]/10 text-[#004242]',
  user:     'bg-[#c7e6e5] text-[#476363]',
  customer: 'bg-[#c7e6e5] text-[#476363]',
};

const ROLES = ['', 'admin', 'owner', 'user', 'customer'];

export default function AdminUsers() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [role, setRole] = useState('');
  const [pending, setPending] = useState(false);
  const [page, setPage] = useState(1);
  const [confirm, setConfirm] = useState(null); // { type: 'approve'|'reject', user }

  const params = new URLSearchParams({ page });
  if (search)  params.set('search',  search);
  if (role)    params.set('role',    role);
  if (pending) params.set('pending', '1');

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', search, role, pending, page],
    queryFn: () => api.get(`/admin/users?${params}`).then(r => r.data),
    keepPreviousData: true,
  });

  const approveMutation = useMutation({
    mutationFn: id => api.patch(`/admin/users/${id}/approve`),
    onSuccess: () => { qc.invalidateQueries(['admin-users']); setConfirm(null); },
  });

  const rejectMutation = useMutation({
    mutationFn: id => api.delete(`/admin/users/${id}`),
    onSuccess: () => { qc.invalidateQueries(['admin-users']); setConfirm(null); },
  });

  const users = data?.data ?? [];
  const meta = data ?? {};

  return (
    <div className="space-y-6">
      {/* Confirm modal */}
      {confirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
          <div className="bg-white rounded-[0.5rem] p-6 w-full max-w-sm shadow-xl">
            <h3 className="font-headline font-bold text-lg text-[#191c1d] mb-2">
              {confirm.type === 'approve' ? 'Approve User?' : 'Reject & Delete User?'}
            </h3>
            <p className="text-sm text-[#6f7979] mb-6">
              {confirm.type === 'approve'
                ? `Approve ${confirm.user.name} as an owner?`
                : `This will permanently delete ${confirm.user.name}. This cannot be undone.`}
            </p>
            <div className="flex gap-3">
              <button onClick={() => setConfirm(null)}
                className="flex-1 py-2 rounded-lg bg-[#f3f4f5] text-[#3f4948] text-sm font-semibold hover:bg-[#e1e3e4] transition-colors">Cancel</button>
              <button
                onClick={() => confirm.type === 'approve'
                  ? approveMutation.mutate(confirm.user.id)
                  : rejectMutation.mutate(confirm.user.id)}
                disabled={approveMutation.isPending || rejectMutation.isPending}
                className={`flex-1 py-2 rounded-lg text-white text-sm font-semibold transition-colors ${
                  confirm.type === 'approve' ? 'bg-[#004242] hover:bg-[#005b5c]' : 'bg-[#ba1a1a] hover:bg-[#ba1a1a]/80'
                }`}
              >
                {confirm.type === 'approve' ? 'Approve' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="font-headline text-2xl font-bold text-[#004242]">Users</h1>
        <span className="text-sm text-[#6f7979]">{meta.total ?? 0} total</span>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-[0.5rem] p-4 flex flex-wrap gap-3 items-center">
        <div className="relative flex-1 min-w-48">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
          <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search name or email…"
            className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
        </div>
        <select value={role} onChange={e => { setRole(e.target.value); setPending(false); setPage(1); }}
          className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm font-medium text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20">
          {ROLES.map(r => <option key={r} value={r}>{r ? r.charAt(0).toUpperCase() + r.slice(1) : 'All Roles'}</option>)}
        </select>
        <button
          onClick={() => { setPending(p => !p); setRole(''); setPage(1); }}
          className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-colors ${
            pending ? 'bg-[#5d2e10]/10 text-[#5d2e10]' : 'bg-[#f3f4f5] text-[#3f4948] hover:bg-[#e1e3e4]'
          }`}
        >
          <span className="material-symbols-outlined text-base">pending</span>
          Pending Approvals
        </button>
      </div>

      {/* Table */}
      <div className="bg-white rounded-[0.5rem] overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
          </div>
        ) : users.length === 0 ? (
          <div className="text-center py-16 text-[#6f7979]">
            <span className="material-symbols-outlined text-4xl block mb-2">group</span>
            No users found
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-[#f3f4f5]">
                  {['User', 'Role', 'Salons', 'Approved', 'Joined', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-[#6f7979] uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {users.map(u => (
                  <tr key={u.id} className="border-b border-[#f3f4f5] hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-[#005b5c] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                          {u.name?.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()}
                        </div>
                        <div>
                          <p className="font-semibold text-[#191c1d]">{u.name}</p>
                          <p className="text-xs text-[#6f7979]">{u.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${ROLE_BADGE[u.role] ?? 'bg-[#e1e3e4] text-[#3f4948]'}`}>
                        {u.role ?? 'none'}
                      </span>
                    </td>
                    <td className="px-5 py-4 text-[#3f4948]">{u.salons_count ?? 0}</td>
                    <td className="px-5 py-4">
                      {u.is_approved ? (
                        <span className="material-symbols-outlined text-[#004242]">check_circle</span>
                      ) : (
                        <span className="material-symbols-outlined text-[#6f7979]">radio_button_unchecked</span>
                      )}
                    </td>
                    <td className="px-5 py-4 text-xs text-[#6f7979] whitespace-nowrap">
                      {new Date(u.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1">
                        {!u.is_approved && u.role !== 'admin' && (
                          <button
                            onClick={() => setConfirm({ type: 'approve', user: u })}
                            className="p-1.5 rounded-lg bg-[#004242]/10 text-[#004242] hover:bg-[#004242]/20 transition-colors"
                            title="Approve"
                          >
                            <span className="material-symbols-outlined text-base">check</span>
                          </button>
                        )}
                        {u.role !== 'admin' && (
                          <button
                            onClick={() => setConfirm({ type: 'reject', user: u })}
                            className="p-1.5 rounded-lg bg-[#ffdad6] text-[#ba1a1a] hover:bg-[#ba1a1a]/20 transition-colors"
                            title="Delete"
                          >
                            <span className="material-symbols-outlined text-base">delete</span>
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
    </div>
  );
}
