import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const EMPTY = { name: '', slug: '', meta_title: '', meta_description: '', is_active: true };

function slugify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

function DepartmentModal({ initial, onClose }) {
  const qc = useQueryClient();
  const isEdit = !!initial?.id;
  const [form, setForm] = useState(initial ? {
    name:             initial.name             ?? '',
    slug:             initial.slug             ?? '',
    meta_title:       initial.meta_title       ?? '',
    meta_description: initial.meta_description ?? '',
    is_active:        initial.is_active        ?? true,
  } : EMPTY);
  const [error, setError] = useState('');

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.put(`/admin/departments/${initial.id}`, data).then(r => r.data)
      : api.post('/admin/departments', data).then(r => r.data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-departments'] }); onClose(); },
    onError: e => setError(
      e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ') ?? 'Error'
    ),
  });

  const inp = 'w-full bg-[#f3f4f5] border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-[#6f7979] focus:outline-none focus:border-[#004242]/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-[#6f7979] uppercase tracking-widest mb-1.5';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-lg font-bold">{isEdit ? 'Edit Department' : 'New Department'}</h2>
          <button onClick={onClose} className="p-2 rounded-full hover:bg-[#f3f4f5] text-[#6f7979]">
            <span className="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        <form onSubmit={e => { e.preventDefault(); setError(''); mutate(form); }} className="overflow-y-auto flex-1">
          <div className="px-6 py-5 space-y-4">
            {error && <div className="bg-[#ffdad6] text-[#ba1a1a] text-sm font-medium px-4 py-3 rounded-xl border-l-4 border-[#ba1a1a]">{error}</div>}

            <div>
              <label className={lbl}>Name *</label>
              <input value={form.name} onChange={e => {
                const v = e.target.value;
                set('name', v);
                if (!isEdit || !form.slug) set('slug', slugify(v));
              }} required className={inp} placeholder="e.g. Beauty" />
            </div>
            <div>
              <label className={lbl}>Slug</label>
              <input value={form.slug} onChange={e => set('slug', e.target.value)} className={inp} placeholder="auto-generated" />
            </div>
            <div>
              <label className={lbl}>Meta Title</label>
              <input value={form.meta_title} onChange={e => set('meta_title', e.target.value)} className={inp} placeholder="SEO title" />
            </div>
            <div>
              <label className={lbl}>Meta Description</label>
              <textarea value={form.meta_description} onChange={e => set('meta_description', e.target.value)}
                rows={3} className={`${inp} resize-none`} placeholder="SEO description" />
            </div>
            <label className="flex items-center gap-2.5 cursor-pointer select-none">
              <div onClick={() => set('is_active', !form.is_active)}
                className={`w-10 h-6 rounded-full transition-colors relative cursor-pointer ${form.is_active ? 'bg-[#004242]' : 'bg-[#bec8c8]'}`}>
                <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all ${form.is_active ? 'left-5' : 'left-1'}`} />
              </div>
              <span className="text-sm font-medium text-[#191c1d]">Active</span>
            </label>
          </div>

          <div className="px-6 py-4 border-t border-[#e1e3e4] flex justify-end gap-3">
            <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl border border-[#e1e3e4] text-sm font-semibold text-[#3f4948] hover:bg-[#f3f4f5]">Cancel</button>
            <button type="submit" disabled={isPending} className="px-6 py-2.5 rounded-xl bg-[#004242] text-white text-sm font-bold hover:bg-[#005b5c] disabled:opacity-60 flex items-center gap-2">
              {isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              {isEdit ? 'Save Changes' : 'Create Department'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function AdminDepartments() {
  const qc = useQueryClient();
  const [modal, setModal]     = useState(null);
  const [search, setSearch]   = useState('');
  const [deleteId, setDeleteId] = useState(null);

  const { data: departments = [], isLoading } = useQuery({
    queryKey: ['admin-departments'],
    queryFn: () => api.get('/admin/departments').then(r => r.data),
  });

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/admin/departments/${id}`).then(r => r.data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-departments'] }); setDeleteId(null); },
  });

  const filtered = departments.filter(d =>
    !search || d.name.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-headline text-2xl font-bold text-[#004242]">Departments</h1>
          <p className="text-sm text-[#6f7979] mt-0.5">{departments.length} department{departments.length !== 1 ? 's' : ''}</p>
        </div>
        <button onClick={() => setModal('create')}
          className="flex items-center gap-2 px-5 py-2.5 bg-[#004242] text-white text-sm font-bold rounded-xl hover:bg-[#005b5c] transition-colors">
          <span className="material-symbols-outlined text-base">add</span>
          New Department
        </button>
      </div>

      <div className="bg-white rounded-[0.5rem] p-4">
        <div className="relative max-w-sm">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search departments…"
            className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
        </div>
      </div>

      <div className="bg-white rounded-[0.5rem] overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" /></div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-16 text-[#6f7979]">
            <span className="material-symbols-outlined text-4xl block mb-2">business</span>
            No departments found
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-[#f3f4f5]">
                  {['Name', 'Slug', 'Categories', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-[#6f7979] uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map(dept => (
                  <tr key={dept.id} className="border-b border-[#f3f4f5] hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4 font-semibold text-[#191c1d]">{dept.name}</td>
                    <td className="px-5 py-4 text-[#6f7979] text-xs font-mono">{dept.slug}</td>
                    <td className="px-5 py-4 text-[#3f4948]">{dept.categories_count ?? 0}</td>
                    <td className="px-5 py-4">
                      <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${dept.is_active ? 'bg-[#004242]/10 text-[#004242]' : 'bg-[#e1e3e4] text-[#6f7979]'}`}>
                        {dept.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1">
                        <button onClick={() => setModal(dept)} className="p-1.5 rounded-lg text-[#6f7979] hover:bg-[#f3f4f5] hover:text-[#004242] transition-colors" title="Edit">
                          <span className="material-symbols-outlined text-base">edit</span>
                        </button>
                        {deleteId === dept.id ? (
                          <div className="flex items-center gap-1 bg-[#ffdad6] rounded-lg px-2 py-1">
                            <span className="text-xs font-semibold text-[#ba1a1a]">Delete?</span>
                            <button onClick={() => deleteMut.mutate(dept.id)} disabled={deleteMut.isPending}
                              className="text-xs font-bold text-[#ba1a1a] hover:underline px-1">Yes</button>
                            <button onClick={() => setDeleteId(null)} className="text-xs font-bold text-[#6f7979] hover:underline px-1">No</button>
                          </div>
                        ) : (
                          <button onClick={() => setDeleteId(dept.id)}
                            className="p-1.5 rounded-lg text-[#6f7979] hover:bg-[#ffdad6] hover:text-[#ba1a1a] transition-colors" title="Delete">
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

      {modal && <DepartmentModal initial={modal === 'create' ? null : modal} onClose={() => setModal(null)} />}
    </div>
  );
}
