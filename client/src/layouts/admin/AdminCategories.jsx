import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const EMPTY = { name: '', department_id: '', is_active: true };

function CategoryModal({ initial, departments, onClose }) {
  const qc = useQueryClient();
  const isEdit = !!initial?.id;
  const [form, setForm] = useState(initial ? {
    name:          initial.name             ?? '',
    department_id: initial.department_id    ?? '',
    is_active:     initial.is_active        ?? true,
  } : EMPTY);
  const [error, setError] = useState('');

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.put(`/admin/categories/${initial.id}`, data).then(r => r.data)
      : api.post('/admin/categories', data).then(r => r.data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-categories'] }); onClose(); },
    onError: e => setError(
      e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ') ?? 'Error'
    ),
  });

  const inp = 'w-full bg-ds-surface-container-low border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-ds-outline focus:outline-hidden focus:border-ds-primary/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-ds-outline uppercase tracking-widest mb-1.5';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
        <div className="flex items-center justify-between px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-lg font-bold">{isEdit ? 'Edit Category' : 'New Category'}</h2>
          <button onClick={onClose} className="p-2 rounded-full hover:bg-ds-surface-container-low text-ds-outline">
            <span className="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        <form onSubmit={e => { e.preventDefault(); setError(''); mutate(form); }} className="px-6 py-5 space-y-4">
          {error && <div className="bg-ds-error-container text-ds-error text-sm font-medium px-4 py-3 rounded-xl border-l-4 border-ds-error">{error}</div>}

          <div>
            <label className={lbl}>Name *</label>
            <input value={form.name} onChange={e => set('name', e.target.value)} required className={inp} placeholder="e.g. Hair Cut" />
          </div>
          <div>
            <label className={lbl}>Department *</label>
            <select value={form.department_id} onChange={e => set('department_id', e.target.value)} required className={inp}>
              <option value="">Select department</option>
              {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
            </select>
          </div>
          <label className="flex items-center gap-2.5 cursor-pointer select-none">
            <div onClick={() => set('is_active', !form.is_active)}
              className={`w-10 h-6 rounded-full transition-colors relative cursor-pointer ${form.is_active ? 'bg-ds-primary' : 'bg-ds-outline-variant'}`}>
              <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all ${form.is_active ? 'left-5' : 'left-1'}`} />
            </div>
            <span className="text-sm font-medium text-[#191c1d]">Active</span>
          </label>

          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl border border-[#e1e3e4] text-sm font-semibold text-ds-on-surface-variant hover:bg-ds-surface-container-low">Cancel</button>
            <button type="submit" disabled={isPending} className="px-6 py-2.5 rounded-xl bg-ds-primary text-white text-sm font-bold hover:bg-ds-primary-container disabled:opacity-60 flex items-center gap-2">
              {isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              {isEdit ? 'Save Changes' : 'Create Category'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function AdminCategories() {
  const qc = useQueryClient();
  const [modal, setModal]       = useState(null);
  const [search, setSearch]     = useState('');
  const [deptFilter, setDeptFilter] = useState('');
  const [deleteId, setDeleteId] = useState(null);

  const { data: categories = [], isLoading } = useQuery({
    queryKey: ['admin-categories'],
    queryFn: () => api.get('/admin/categories').then(r => r.data),
  });

  const { data: departments = [] } = useQuery({
    queryKey: ['admin-departments'],
    queryFn: () => api.get('/admin/departments').then(r => r.data),
  });

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/admin/categories/${id}`).then(r => r.data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-categories'] }); setDeleteId(null); },
  });

  const filtered = categories.filter(c => {
    const matchSearch = !search || c.name.toLowerCase().includes(search.toLowerCase());
    const matchDept   = !deptFilter || String(c.department_id) === deptFilter;
    return matchSearch && matchDept;
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-headline text-2xl font-bold text-ds-primary">Categories</h1>
          <p className="text-sm text-ds-outline mt-0.5">{categories.length} categor{categories.length !== 1 ? 'ies' : 'y'}</p>
        </div>
        <button onClick={() => setModal('create')}
          className="flex items-center gap-2 px-5 py-2.5 bg-ds-primary text-white text-sm font-bold rounded-xl hover:bg-ds-primary-container transition-colors">
          <span className="material-symbols-outlined text-base">add</span>
          New Category
        </button>
      </div>

      <div className="bg-white rounded-lg p-4 flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-48">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-ds-outline text-base">search</span>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search categories…"
            className="w-full bg-ds-surface-container-low border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20" />
        </div>
        <select value={deptFilter} onChange={e => setDeptFilter(e.target.value)}
          className="bg-ds-surface-container-low border-none rounded-full py-2 px-4 text-sm font-medium text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20">
          <option value="">All Departments</option>
          {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
        </select>
      </div>

      <div className="bg-white rounded-lg overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" /></div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-16 text-ds-outline">
            <span className="material-symbols-outlined text-4xl block mb-2">category</span>
            No categories found
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-ds-surface-container-low">
                  {['Name', 'Department', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-ds-outline uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map(cat => (
                  <tr key={cat.id} className="border-b border-ds-surface-container-low hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4 font-semibold text-[#191c1d]">{cat.name}</td>
                    <td className="px-5 py-4 text-ds-on-surface-variant">{cat.department?.name ?? '—'}</td>
                    <td className="px-5 py-4">
                      <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${cat.is_active ? 'bg-ds-primary/10 text-ds-primary' : 'bg-[#e1e3e4] text-ds-outline'}`}>
                        {cat.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1">
                        <button onClick={() => setModal(cat)} className="p-1.5 rounded-lg text-ds-outline hover:bg-ds-surface-container-low hover:text-ds-primary transition-colors" title="Edit">
                          <span className="material-symbols-outlined text-base">edit</span>
                        </button>
                        {deleteId === cat.id ? (
                          <div className="flex items-center gap-1 bg-ds-error-container rounded-lg px-2 py-1">
                            <span className="text-xs font-semibold text-ds-error">Delete?</span>
                            <button onClick={() => deleteMut.mutate(cat.id)} disabled={deleteMut.isPending}
                              className="text-xs font-bold text-ds-error hover:underline px-1">Yes</button>
                            <button onClick={() => setDeleteId(null)} className="text-xs font-bold text-ds-outline hover:underline px-1">No</button>
                          </div>
                        ) : (
                          <button onClick={() => setDeleteId(cat.id)}
                            className="p-1.5 rounded-lg text-ds-outline hover:bg-ds-error-container hover:text-ds-error transition-colors" title="Delete">
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
      </div>

      {modal && (
        <CategoryModal
          initial={modal === 'create' ? null : modal}
          departments={departments}
          onClose={() => setModal(null)}
        />
      )}
    </div>
  );
}
