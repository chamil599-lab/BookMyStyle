import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const STATUS_BADGE = {
  published:   'bg-[#004242]/10 text-[#004242]',
  unpublished: 'bg-[#e1e3e4] text-[#6f7979]',
};

const EMPTY_SVC = {
  title: '', description: '', department_id: '', category_id: '',
  price: '', duration: '', status: 'published',
};

function ServiceModal({ initial, onClose, onSaved }) {
  const [form, setForm]   = useState(initial ? {
    title: initial.title, description: initial.description ?? '',
    department_id: initial.department_id ?? '',
    category_id: initial.category_id ?? '',
    price: initial.price, duration: initial.duration,
    status: initial.status,
  } : EMPTY_SVC);
  const [error, setError] = useState('');
  const isEdit = !!initial?.id;

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { data: departments = [] } = useQuery({
    queryKey: ['owner-departments'],
    queryFn: () => api.get('/owner/departments').then(r => r.data),
  });

  const { data: categories = [] } = useQuery({
    queryKey: ['owner-categories', form.department_id],
    queryFn: () => api.get(`/owner/categories?department_id=${form.department_id}`).then(r => r.data),
    enabled: !!form.department_id,
  });

  const handleDeptChange = v => {
    set('department_id', v);
    set('category_id', '');
  };

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.put(`/owner/services/${initial.id}`, data).then(r => r.data)
      : api.post('/owner/services', data).then(r => r.data),
    onSuccess: saved => { onSaved(saved); onClose(); },
    onError: e => setError(e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ') ?? 'An error occurred'),
  });

  const handleSubmit = e => {
    e.preventDefault();
    setError('');
    mutate(form);
  };

  const inp = 'w-full bg-[#f3f4f5] border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-[#6f7979] focus:outline-none focus:border-[#004242]/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-[#6f7979] uppercase tracking-widest mb-1.5';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-lg font-bold text-[#191c1d]">{isEdit ? 'Edit Service' : 'Add New Service'}</h2>
          <button onClick={onClose} className="p-2 rounded-full hover:bg-[#f3f4f5] transition-colors text-[#6f7979]">
            <span className="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="overflow-y-auto flex-1">
          <div className="px-6 py-5 space-y-4">
            {error && (
              <div className="bg-[#ffdad6] text-[#ba1a1a] text-sm font-medium px-4 py-3 rounded-xl border-l-4 border-[#ba1a1a]">
                {error}
              </div>
            )}

            <div>
              <label className={lbl}>Service Title *</label>
              <input value={form.title} onChange={e => set('title', e.target.value)} required className={inp} placeholder="e.g. Bridal Makeup" />
            </div>

            <div>
              <label className={lbl}>Description *</label>
              <textarea value={form.description} onChange={e => set('description', e.target.value)} required rows={3} className={`${inp} resize-none`} placeholder="Describe what this service includes…" />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Department *</label>
                <select value={form.department_id} onChange={e => handleDeptChange(e.target.value)} required className={inp}>
                  <option value="">Select department</option>
                  {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                </select>
              </div>
              <div>
                <label className={lbl}>Category *</label>
                <select value={form.category_id} onChange={e => set('category_id', e.target.value)} required className={inp} disabled={!form.department_id}>
                  <option value="">Select category</option>
                  {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className={lbl}>Price (LKR) *</label>
                <input type="number" min="0" step="0.01" value={form.price} onChange={e => set('price', e.target.value)} required className={inp} placeholder="0.00" />
              </div>
              <div>
                <label className={lbl}>Duration (min) *</label>
                <input type="number" min="1" value={form.duration} onChange={e => set('duration', e.target.value)} required className={inp} placeholder="60" />
              </div>
              <div>
                <label className={lbl}>Status *</label>
                <select value={form.status} onChange={e => set('status', e.target.value)} required className={inp}>
                  <option value="published">Published</option>
                  <option value="unpublished">Unpublished</option>
                </select>
              </div>
            </div>
          </div>

          <div className="px-6 py-4 border-t border-[#e1e3e4] flex items-center justify-end gap-3">
            <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl border border-[#e1e3e4] text-sm font-semibold text-[#3f4948] hover:bg-[#f3f4f5] transition-colors">
              Cancel
            </button>
            <button type="submit" disabled={isPending}
              className="px-6 py-2.5 rounded-xl bg-[#004242] text-white text-sm font-bold hover:bg-[#005b5c] transition-colors disabled:opacity-60 flex items-center gap-2">
              {isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              {isEdit ? 'Save Changes' : 'Create Service'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function OwnerServices() {
  const qc = useQueryClient();
  const [search, setSearch]     = useState('');
  const [status, setStatus]     = useState('');
  const [page, setPage]         = useState(1);
  const [modal, setModal]       = useState(null);
  const [deleteId, setDeleteId] = useState(null);

  const { data: limits } = useQuery({
    queryKey: ['owner-limits'],
    queryFn: () => api.get('/owner/limits').then(r => r.data),
  });

  const svcUsed  = limits?.services?.used ?? 0;
  const svcMax   = limits?.services?.max ?? null;
  const atLimit  = svcMax !== null && svcUsed >= svcMax;

  const params = new URLSearchParams({ page });
  if (search) params.set('search', search);
  if (status) params.set('status', status);

  const { data, isLoading } = useQuery({
    queryKey: ['owner-services', search, status, page],
    queryFn: () => api.get(`/owner/services?${params}`).then(r => r.data),
    keepPreviousData: true,
  });

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/owner/services/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['owner-services'] }); setDeleteId(null); },
  });

  const handleSaved = () => qc.invalidateQueries({ queryKey: ['owner-services'] });

  const services = data?.data ?? [];
  const meta = data ?? {};

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-headline text-2xl font-bold text-[#004242]">Services</h1>
          {svcMax !== null && (
            <p className="text-xs text-[#6f7979] mt-0.5 font-medium">
              {svcUsed} / {svcMax} services used
              {atLimit && <span className="ml-2 text-[#ba1a1a] font-bold">— limit reached</span>}
            </p>
          )}
        </div>
        <button
          onClick={() => !atLimit && setModal('create')}
          disabled={atLimit}
          title={atLimit ? `Your plan allows a maximum of ${svcMax} service(s). Upgrade to add more.` : undefined}
          className={`flex items-center gap-2 px-5 py-2.5 text-sm font-bold rounded-xl transition-colors ${
            atLimit
              ? 'bg-[#e1e3e4] text-[#6f7979] cursor-not-allowed'
              : 'bg-[#004242] text-white hover:bg-[#005b5c]'
          }`}>
          <span className="material-symbols-outlined text-base">{atLimit ? 'lock' : 'add'}</span>
          Add Service
        </button>
      </div>

      {atLimit && (
        <div className="bg-[#fff3e0] border border-[#f4a261]/40 rounded-xl px-4 py-3 flex items-center gap-3">
          <span className="material-symbols-outlined text-[#e65100]">info</span>
          <p className="text-sm text-[#e65100] font-medium">
            You've reached your plan limit of <strong>{svcMax}</strong> service(s).
            <a href="/owner/subscription" className="ml-1 font-bold underline">Upgrade your plan</a> to add more.
          </p>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-[0.5rem] p-4 flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-48">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
          <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search service name…"
            className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
        </div>
        <select value={status} onChange={e => { setStatus(e.target.value); setPage(1); }}
          className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm font-medium text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20">
          <option value="">All Statuses</option>
          <option value="published">Published</option>
          <option value="unpublished">Unpublished</option>
        </select>
      </div>

      {/* Grid */}
      {isLoading ? (
        <div className="flex items-center justify-center h-48">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
        </div>
      ) : services.length === 0 ? (
        <div className="bg-white rounded-[0.5rem] text-center py-16 text-[#6f7979]">
          <span className="material-symbols-outlined text-4xl block mb-2">content_cut</span>
          <p className="font-semibold">No services found</p>
          <p className="text-sm mt-1 mb-5">Create your first service to offer bookings.</p>
          <button onClick={() => setModal('create')}
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-[#004242] text-white text-sm font-bold rounded-xl hover:bg-[#005b5c] transition-colors">
            <span className="material-symbols-outlined text-base">add</span>
            Add Service
          </button>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            {services.map(svc => (
              <div key={svc.id} className="relative overflow-hidden rounded-[1rem] border border-[#004242]/10 bg-[linear-gradient(180deg,#ffffff_0%,#f3fbfa_100%)] p-5 shadow-[0_18px_44px_rgba(0,66,66,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_22px_52px_rgba(0,66,66,0.12)]">
                <div className="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-[#8bd0d1]/15 blur-2xl" />
                <div className="flex items-start justify-between gap-3 mb-4">
                  <div className="w-12 h-12 rounded-2xl bg-[linear-gradient(135deg,#004242_0%,#0c5455_100%)] flex items-center justify-center flex-shrink-0 shadow-[0_12px_24px_rgba(0,66,66,0.18)]">
                    <span className="material-symbols-outlined text-xl text-white">content_cut</span>
                  </div>
                  <div className="flex items-center gap-1.5 mt-1">
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_BADGE[svc.status] ?? 'bg-[#e1e3e4] text-[#6f7979]'}`}>
                      {svc.status}
                    </span>
                    <button onClick={() => setModal(svc)}
                      className="p-1 rounded-lg text-[#6f7979] hover:bg-[#f3f4f5] hover:text-[#004242] transition-colors" title="Edit">
                      <span className="material-symbols-outlined text-base">edit</span>
                    </button>
                    {deleteId === svc.id ? (
                      <div className="flex items-center gap-1 bg-[#ffdad6] rounded-lg px-2 py-0.5">
                        <span className="text-xs font-semibold text-[#ba1a1a]">Delete?</span>
                        <button onClick={() => deleteMut.mutate(svc.id)} disabled={deleteMut.isPending}
                          className="text-xs font-bold text-[#ba1a1a] hover:underline px-1">Yes</button>
                        <button onClick={() => setDeleteId(null)} className="text-xs font-bold text-[#6f7979] hover:underline px-1">No</button>
                      </div>
                    ) : (
                      <button onClick={() => setDeleteId(svc.id)}
                        className="p-1 rounded-lg text-[#6f7979] hover:bg-[#ffdad6] hover:text-[#ba1a1a] transition-colors" title="Delete">
                        <span className="material-symbols-outlined text-base">delete</span>
                      </button>
                    )}
                  </div>
                </div>

                <h3 className="font-headline font-bold text-[#033737] leading-snug">{svc.title}</h3>
                {svc.description && (
                  <p className="text-xs text-[#547170] mt-1 line-clamp-2">{svc.description.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()}</p>
                )}

                <div className="mt-4 space-y-2 text-sm">
                  {svc.department && (
                    <div className="flex items-center gap-2 text-[#285252]">
                      <span className="material-symbols-outlined text-base text-[#004242]">category</span>
                      {svc.department.name}{svc.category ? ` › ${svc.category.name}` : ''}
                    </div>
                  )}
                  <div className="flex items-center gap-2 text-[#285252]">
                    <span className="material-symbols-outlined text-base text-[#004242]">schedule</span>
                    {svc.duration} min
                  </div>
                </div>

                <div className="mt-5 pt-4 border-t border-[#004242]/10 flex items-center justify-between">
                  <p className="font-headline font-extrabold text-lg text-[#004242]">
                    LKR {Number(svc.price ?? 0).toLocaleString()}
                  </p>
                  <div className="rounded-2xl px-3 py-2 text-right bg-[linear-gradient(135deg,#004242_0%,#0f5f60_100%)] shadow-[0_10px_24px_rgba(0,66,66,0.14)]">
                    <p className="font-bold text-white">{svc.bookings_count ?? 0}</p>
                    <p className="text-[10px] text-[#b9ecea] uppercase tracking-widest font-semibold">Bookings</p>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {meta.last_page > 1 && (
            <div className="flex items-center justify-between">
              <p className="text-xs text-[#6f7979]">Page {meta.current_page} of {meta.last_page}</p>
              <div className="flex gap-2">
                <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={meta.current_page <= 1}
                  className="px-3 py-1.5 rounded-lg bg-[#f3f4f5] text-[#3f4948] text-xs font-semibold disabled:opacity-40 hover:bg-[#e1e3e4] transition-colors">Prev</button>
                <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={meta.current_page >= meta.last_page}
                  className="px-3 py-1.5 rounded-lg bg-[#004242] text-white text-xs font-semibold disabled:opacity-40 hover:bg-[#005b5c] transition-colors">Next</button>
              </div>
            </div>
          )}
        </>
      )}

      {modal && (
        <ServiceModal
          initial={modal === 'create' ? null : modal}
          onClose={() => setModal(null)}
          onSaved={handleSaved}
        />
      )}
    </div>
  );
}
