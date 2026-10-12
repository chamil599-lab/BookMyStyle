import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const EMPTY = {
  name: '', price: '', duration_days: '', order_column: '',
  max_salons: '', max_staff: '', max_services: '', max_bookings_per_month: '',
  analytics: false, priority_support: false, calendar: false, is_active: true, is_popular: false,
  start_date: '', end_date: '',
};

function PackageModal({ initial, onClose }) {
  const qc = useQueryClient();
  const isEdit = !!initial?.id;
  const [form, setForm] = useState(initial ? {
    name:                   initial.name                   ?? '',
    price:                  initial.price                  ?? '',
    duration_days:          initial.duration_days          ?? '',
    order_column:           initial.order_column           ?? '',
    max_salons:             initial.max_salons             ?? '',
    max_staff:              initial.max_staff              ?? '',
    max_services:           initial.max_services           ?? '',
    max_bookings_per_month: initial.max_bookings_per_month ?? '',
    analytics:              !!initial.analytics,
    priority_support:       !!initial.priority_support,
    calendar:               !!initial.calendar,
    is_active:              initial.is_active ?? true,
    is_popular:             !!initial.is_popular,
    start_date:             initial.start_date ? initial.start_date.slice(0, 10) : '',
    end_date:               initial.end_date   ? initial.end_date.slice(0, 10)   : '',
  } : EMPTY);
  const [error, setError] = useState('');

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.put(`/admin/packages/${initial.id}`, data).then(r => r.data)
      : api.post('/admin/packages', data).then(r => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-packages'] });
      qc.invalidateQueries({ queryKey: ['admin-top-packages'] });
      onClose();
    },
    onError: e => setError(
      e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ') ?? 'Error'
    ),
  });

  const handleSubmit = e => {
    e.preventDefault();
    setError('');
    const payload = {
      ...form,
      price:                  Number(form.price),
      duration_days:          Number(form.duration_days),
      max_salons:             form.max_salons             !== '' ? Number(form.max_salons)             : null,
      max_staff:              form.max_staff              !== '' ? Number(form.max_staff)              : null,
      max_services:           form.max_services           !== '' ? Number(form.max_services)           : null,
      max_bookings_per_month: form.max_bookings_per_month !== '' ? Number(form.max_bookings_per_month) : null,
      order_column:           form.order_column           !== '' ? Number(form.order_column)           : null,
    };
    mutate(payload);
  };

  const inp = 'w-full bg-ds-surface-container-low border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-ds-outline focus:outline-hidden focus:border-ds-primary/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-ds-outline uppercase tracking-widest mb-1.5';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-lg font-bold">{isEdit ? 'Edit Package' : 'New Package'}</h2>
          <button onClick={onClose} className="p-2 rounded-full hover:bg-ds-surface-container-low text-ds-outline">
            <span className="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="overflow-y-auto flex-1">
          <div className="px-6 py-5 space-y-4">
            {error && <div className="bg-ds-error-container text-ds-error text-sm font-medium px-4 py-3 rounded-xl border-l-4 border-ds-error">{error}</div>}

            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <label className={lbl}>Name *</label>
                <input value={form.name} onChange={e => set('name', e.target.value)} required className={inp} placeholder="e.g. Pro Plan" />
              </div>
              <div>
                <label className={lbl}>Price (LKR) *</label>
                <input type="number" min="0" step="0.01" value={form.price} onChange={e => set('price', e.target.value)} required className={inp} placeholder="0.00" />
              </div>
              <div>
                <label className={lbl}>Duration (days) *</label>
                <input type="number" min="1" value={form.duration_days} onChange={e => set('duration_days', e.target.value)} required className={inp} placeholder="30" />
              </div>
            </div>

            <p className="text-xs font-bold text-ds-outline uppercase tracking-widest">Limits <span className="normal-case font-normal">(leave blank for unlimited)</span></p>
            <div className="grid grid-cols-2 gap-4">
              {[
                { key: 'max_salons',             label: 'Max Salons' },
                { key: 'max_staff',              label: 'Max Staff' },
                { key: 'max_services',           label: 'Max Services' },
                { key: 'max_bookings_per_month', label: 'Max Bookings/Month' },
              ].map(({ key, label }) => (
                <div key={key}>
                  <label className={lbl}>{label}</label>
                  <input type="number" min="1" value={form[key]} onChange={e => set(key, e.target.value)} className={inp} placeholder="Unlimited" />
                </div>
              ))}
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Display Order</label>
                <input type="number" min="0" value={form.order_column} onChange={e => set('order_column', e.target.value)} className={inp} placeholder="0" />
              </div>
            </div>


            <div className="grid grid-cols-2 gap-3 pt-1">
              {[
                { key: 'analytics',        label: 'Analytics' },
                { key: 'priority_support', label: 'Priority Support' },
                { key: 'calendar',         label: 'Calendar' },
                { key: 'is_active',        label: 'Active' },
                { key: 'is_popular',       label: 'Mark as Popular' },
              ].map(({ key, label }) => (
                <label key={key} className="flex items-center gap-2.5 cursor-pointer select-none">
                  <div
                    onClick={() => set(key, !form[key])}
                    className={`w-10 h-6 rounded-full transition-colors relative cursor-pointer ${form[key] ? 'bg-ds-primary' : 'bg-ds-outline-variant'}`}
                  >
                    <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all ${form[key] ? 'left-5' : 'left-1'}`} />
                  </div>
                  <span className="text-sm font-medium text-[#191c1d]">{label}</span>
                </label>
              ))}
            </div>
          </div>

          <div className="px-6 py-4 border-t border-[#e1e3e4] flex justify-end gap-3">
            <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl border border-[#e1e3e4] text-sm font-semibold text-ds-on-surface-variant hover:bg-ds-surface-container-low">Cancel</button>
            <button type="submit" disabled={isPending} className="px-6 py-2.5 rounded-xl bg-ds-primary text-white text-sm font-bold hover:bg-ds-primary-container disabled:opacity-60 flex items-center gap-2">
              {isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              {isEdit ? 'Save Changes' : 'Create Package'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function fmtPrice(n) {
  return `LKR ${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
}

export default function AdminPackages() {
  const qc = useQueryClient();
  const [modal, setModal]     = useState(null);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteError, setDeleteError] = useState('');

  const { data: packages = [], isLoading } = useQuery({
    queryKey: ['admin-packages'],
    queryFn: () => api.get('/admin/packages').then(r => r.data),
  });

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/admin/packages/${id}`).then(r => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-packages'] });
      qc.invalidateQueries({ queryKey: ['admin-top-packages'] });
      setDeleteId(null);
      setDeleteError('');
    },
    onError: e => setDeleteError(e.response?.data?.message ?? 'Failed to delete'),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-headline text-2xl font-bold text-ds-primary">Subscription Packages</h1>
          <p className="text-sm text-ds-outline mt-0.5">{packages.length} package{packages.length !== 1 ? 's' : ''}</p>
        </div>
        <button onClick={() => setModal('create')}
          className="flex items-center gap-2 px-5 py-2.5 bg-ds-primary text-white text-sm font-bold rounded-xl hover:bg-ds-primary-container transition-colors">
          <span className="material-symbols-outlined text-base">add</span>
          New Package
        </button>
      </div>

      <div className="bg-white rounded-lg overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" /></div>
        ) : packages.length === 0 ? (
          <div className="text-center py-16 text-ds-outline">
            <span className="material-symbols-outlined text-4xl block mb-2">inventory_2</span>
            No packages yet
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-ds-surface-container-low">
                  {['Name', 'Price', 'Duration', 'Limits', 'Features', 'Order', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-ds-outline uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {packages.map(pkg => (
                  <tr key={pkg.id} className="border-b border-ds-surface-container-low hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4">
                      <p className="font-bold text-[#191c1d]">{pkg.name}</p>
                      {pkg.is_popular && <span className="text-[10px] font-bold bg-ds-tertiary-fixed text-ds-tertiary px-2 py-0.5 rounded-full">Popular</span>}
                    </td>
                    <td className="px-5 py-4 font-semibold text-ds-primary whitespace-nowrap">{fmtPrice(pkg.price)}</td>
                    <td className="px-5 py-4 text-ds-on-surface-variant whitespace-nowrap">{pkg.duration_days}d</td>
                    <td className="px-5 py-4">
                      <div className="text-xs text-ds-on-surface-variant space-y-0.5">
                        {pkg.max_salons             != null && <p>{pkg.max_salons} salons</p>}
                        {pkg.max_staff              != null && <p>{pkg.max_staff} staff</p>}
                        {pkg.max_services           != null && <p>{pkg.max_services} services</p>}
                        {pkg.max_bookings_per_month != null && <p>{pkg.max_bookings_per_month} bkgs/mo</p>}
                        {pkg.max_salons == null && pkg.max_services == null && <p className="text-ds-outline">Unlimited</p>}
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex flex-wrap gap-1">
                        {pkg.analytics       && <span className="text-[10px] bg-ds-secondary-container text-ds-secondary px-2 py-0.5 rounded-full font-semibold">Analytics</span>}
                        {pkg.priority_support && <span className="text-[10px] bg-ds-primary/10 text-ds-primary px-2 py-0.5 rounded-full font-semibold">Priority</span>}
                        {pkg.calendar        && <span className="text-[10px] bg-[#e8f4f4] text-ds-primary px-2 py-0.5 rounded-full font-semibold">Calendar</span>}
                        {!pkg.analytics && !pkg.priority_support && !pkg.calendar && <span className="text-ds-outline text-xs">—</span>}
                      </div>
                    </td>
                    <td className="px-5 py-4 text-ds-outline text-center">{pkg.order_column ?? '—'}</td>
                    <td className="px-5 py-4">
                      <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${pkg.is_active ? 'bg-ds-primary/10 text-ds-primary' : 'bg-[#e1e3e4] text-ds-outline'}`}>
                        {pkg.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1">
                        <button onClick={() => setModal(pkg)} className="p-1.5 rounded-lg text-ds-outline hover:bg-ds-surface-container-low hover:text-ds-primary transition-colors" title="Edit">
                          <span className="material-symbols-outlined text-base">edit</span>
                        </button>
                        {deleteId === pkg.id ? (
                          <div className="flex flex-col gap-1">
                            {deleteError && <p className="text-[10px] text-ds-error max-w-[140px]">{deleteError}</p>}
                            <div className="flex items-center gap-1 bg-ds-error-container rounded-lg px-2 py-1">
                              <span className="text-xs font-semibold text-ds-error">Delete?</span>
                              <button onClick={() => deleteMut.mutate(pkg.id)} disabled={deleteMut.isPending}
                                className="text-xs font-bold text-ds-error hover:underline px-1">Yes</button>
                              <button onClick={() => { setDeleteId(null); setDeleteError(''); }}
                                className="text-xs font-bold text-ds-outline hover:underline px-1">No</button>
                            </div>
                          </div>
                        ) : (
                          <button onClick={() => { setDeleteId(pkg.id); setDeleteError(''); }}
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
        <PackageModal
          initial={modal === 'create' ? null : modal}
          onClose={() => setModal(null)}
        />
      )}
    </div>
  );
}
