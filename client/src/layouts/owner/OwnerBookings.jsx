import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

function openWhatsApp(booking, status) {
  const phone = booking.phone?.replace(/\D/g, '');
  if (!phone) return;
  const date = new Date(booking.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const time = new Date(`1970-01-01T${booking.time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  const service = booking.service?.title ?? 'your service';
  const salon = booking.salon?.name ?? '';
  const message = status === 'approved'
    ? `Hello ${booking.name},\n\nYour booking for *${service}* has been *approved*.\n\n*Date:* ${date}\n*Time:* ${time}\n*Salon:* ${salon}\n\nThank you for booking with us! 😊`
    : status === 'rejected'
    ? `Hello ${booking.name},\n\nUnfortunately, your booking for *${service}* has been *rejected*.\n\n*Date:* ${date}\n*Time:* ${time}\n\nPlease contact us for more details or book another slot.`
    : `Hello ${booking.name},\n\nYour booking for *${service}* is currently *${status}*.\n\n*Date:* ${date}\n*Time:* ${time}\n*Salon:* ${salon}`;
  window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
}

const STATUS_BADGE = {
  pending:   { cls: 'bg-[#ffdbca] text-[#5d2e10]',   icon: 'schedule' },
  approved:  { cls: 'bg-[#004242]/10 text-[#004242]', icon: 'check_circle' },
  completed: { cls: 'bg-[#476363]/10 text-[#476363]', icon: 'task_alt' },
  rejected:  { cls: 'bg-[#ffdad6] text-[#ba1a1a]',   icon: 'cancel' },
};

const STATUSES = ['', 'pending', 'approved', 'completed', 'rejected'];

const EMPTY_BKG = {
  salon_id: '', service_id: '', name: '', email: '',
  phone: '', date: '', time: '', status: 'pending',
};

function StatusBadge({ status }) {
  const cfg = STATUS_BADGE[status] ?? STATUS_BADGE.pending;
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${cfg.cls}`}>
      <span className="material-symbols-outlined" style={{ fontSize: 14 }}>{cfg.icon}</span>
      {status}
    </span>
  );
}

function BookingModal({ initial, salons, onClose, onSaved }) {
  const [form, setForm]   = useState(initial ? {
    salon_id:   initial.salon_id   ?? '',
    service_id: initial.service_id ?? '',
    name:       initial.name       ?? '',
    email:      initial.email      ?? '',
    phone:      initial.phone      ?? '',
    date:       initial.date?.slice(0, 10) ?? '',
    time:       initial.time?.slice(0, 5) ?? '',
    status:     initial.status     ?? 'pending',
  } : EMPTY_BKG);
  const [error, setError] = useState('');
  const isEdit = !!initial?.id;

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { data: services = [] } = useQuery({
    queryKey: ['owner-services-all'],
    queryFn: () => api.get('/owner/services?page=1&per_page=1000').then(r => r.data?.data ?? []),
  });

  const serviceOptions = useMemo(() => {
    const options = [...services];
    const currentService = initial?.service;
    const currentServiceId = initial?.service_id;

    if (
      currentServiceId &&
      currentService &&
      !options.some((service) => service.id?.toString() === currentServiceId.toString())
    ) {
      options.unshift({
        id: currentServiceId,
        title: currentService.title || 'Current selected service',
        price: currentService.price,
      });
    }

    return options;
  }, [services, initial]);

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.put(`/owner/bookings/${initial.id}`, data).then(r => r.data)
      : api.post('/owner/bookings', data).then(r => r.data),
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
          <h2 className="font-headline text-lg font-bold text-[#191c1d]">{isEdit ? 'Edit Booking' : 'New Booking'}</h2>
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

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Salon *</label>
                <select value={form.salon_id} onChange={e => set('salon_id', e.target.value)} required className={inp}>
                  <option value="">Select salon</option>
                  {salons.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>
              </div>
              <div>
                <label className={lbl}>Service *</label>
                <select value={form.service_id} onChange={e => set('service_id', e.target.value)} required className={inp}>
                  <option value="">Select service</option>
                  {serviceOptions.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}
                </select>
              </div>
            </div>

            <div>
              <label className={lbl}>Customer Name *</label>
              <input value={form.name} onChange={e => set('name', e.target.value)} required className={inp} placeholder="Full name" />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Email *</label>
                <input type="email" value={form.email} onChange={e => set('email', e.target.value)} required className={inp} placeholder="customer@email.com" />
              </div>
              <div>
                <label className={lbl}>Phone *</label>
                <input value={form.phone} onChange={e => set('phone', e.target.value)} required className={inp} placeholder="+94 77 123 4567" />
              </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-1">
                <label className={lbl}>Date *</label>
                <input type="date" value={form.date} onChange={e => set('date', e.target.value)} required className={inp} />
              </div>
              <div className="col-span-1">
                <label className={lbl}>Time *</label>
                <input type="time" value={form.time} onChange={e => set('time', e.target.value)} required className={inp} />
              </div>
              <div className="col-span-1">
                <label className={lbl}>Status</label>
                <select value={form.status} onChange={e => set('status', e.target.value)} className={inp}>
                  {['pending', 'approved', 'completed', 'rejected'].map(s => (
                    <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
                  ))}
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
              {isEdit ? 'Save Changes' : 'Create Booking'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function OwnerBookings() {
  const qc = useQueryClient();
  const [search, setSearch]     = useState('');
  const [status, setStatus]     = useState('');
  const [salonId, setSalonId]   = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo]     = useState('');
  const [page, setPage]         = useState(1);
  const [modal, setModal]       = useState(null);
  const [deleteId, setDeleteId] = useState(null);

  const { data: salons = [] } = useQuery({
    queryKey: ['owner-salons-list'],
    queryFn: () => api.get('/owner/salons').then(r => r.data),
  });

  const { data: limits } = useQuery({
    queryKey: ['owner-limits'],
    queryFn: () => api.get('/owner/limits').then(r => r.data),
  });

  const bkgUsed = limits?.bookings?.used ?? 0;
  const bkgMax  = limits?.bookings?.max ?? null;
  const atLimit = bkgMax !== null && bkgUsed >= bkgMax;

  const params = new URLSearchParams({ page });
  if (search)   params.set('search',    search);
  if (status)   params.set('status',    status);
  if (salonId)  params.set('salon_id',  salonId);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo)   params.set('date_to',   dateTo);

  const { data, isLoading } = useQuery({
    queryKey: ['owner-bookings', search, status, salonId, dateFrom, dateTo, page],
    queryFn: () => api.get(`/owner/bookings?${params}`).then(r => r.data),
    placeholderData: (prev) => prev,
  });

  const invalidateAll = () => {
    qc.invalidateQueries({ queryKey: ['owner-bookings'] });
    qc.invalidateQueries({ queryKey: ['owner-services'] });
    qc.invalidateQueries({ queryKey: ['owner-stats'] });
    qc.invalidateQueries({ queryKey: ['owner-bookings-by-status'] });
    qc.invalidateQueries({ queryKey: ['owner-limits'] });
    qc.invalidateQueries({ queryKey: ['owner-analytics'] });
    qc.invalidateQueries({ queryKey: ['owner-top-services'] });
    qc.invalidateQueries({ queryKey: ['owner-profit-breakdown'] });
    qc.invalidateQueries({ queryKey: ['owner-profit-by-month'] });
  };

  const statusMut = useMutation({
    mutationFn: ({ id, status }) => api.patch(`/owner/bookings/${id}/status`, { status }),
    onMutate: async ({ id, status }) => {
      await qc.cancelQueries({ queryKey: ['owner-bookings'] });
      const previousData = qc.getQueriesData({ queryKey: ['owner-bookings'] });
      qc.setQueriesData({ queryKey: ['owner-bookings'] }, old => {
        if (!old?.data) return old;
        return { ...old, data: old.data.map(b => b.id === id ? { ...b, status } : b) };
      });
      return { previousData };
    },
    onError: (_err, _vars, ctx) => {
      ctx?.previousData?.forEach(([key, data]) => qc.setQueryData(key, data));
    },
    onSuccess: invalidateAll,
  });

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/owner/bookings/${id}`),
    onSuccess: () => { invalidateAll(); setDeleteId(null); },
  });

  const handleSaved = () => {
    qc.refetchQueries({ queryKey: ['owner-bookings'] });
    invalidateAll();
  };

  const bookings = data?.data ?? [];
  const meta = data ?? {};

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-headline text-2xl font-bold text-[#004242]">Bookings</h1>
          {bkgMax !== null && (
            <p className="text-xs text-[#6f7979] mt-0.5 font-medium">
              {bkgUsed} / {bkgMax} bookings this month
              {atLimit && <span className="ml-2 text-[#ba1a1a] font-bold">— limit reached</span>}
            </p>
          )}
        </div>
        <button
          onClick={() => !atLimit && setModal('create')}
          disabled={atLimit}
          title={atLimit ? `Your plan allows ${bkgMax} booking(s) per month. Upgrade to add more.` : undefined}
          className={`flex items-center gap-2 px-5 py-2.5 text-sm font-bold rounded-xl transition-colors ${
            atLimit
              ? 'bg-[#e1e3e4] text-[#6f7979] cursor-not-allowed'
              : 'bg-[#004242] text-white hover:bg-[#005b5c]'
          }`}>
          <span className="material-symbols-outlined text-base">{atLimit ? 'lock' : 'add'}</span>
          New Booking
        </button>
      </div>

      {atLimit && (
        <div className="bg-[#fff3e0] border border-[#f4a261]/40 rounded-xl px-4 py-3 flex items-center gap-3">
          <span className="material-symbols-outlined text-[#e65100]">info</span>
          <p className="text-sm text-[#e65100] font-medium">
            You've reached your monthly booking limit of <strong>{bkgMax}</strong>.
            <a href="/owner/subscription" className="ml-1 font-bold underline">Upgrade your plan</a> to continue.
          </p>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-[0.5rem] p-4 flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-48">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
          <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search customer name, email…"
            className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
        </div>
        <select value={status} onChange={e => { setStatus(e.target.value); setPage(1); }}
          className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm font-medium text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20">
          {STATUSES.map(s => <option key={s} value={s}>{s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All Statuses'}</option>)}
        </select>
        {salons.length > 1 && (
          <select value={salonId} onChange={e => { setSalonId(e.target.value); setPage(1); }}
            className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm font-medium text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20">
            <option value="">All Salons</option>
            {salons.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
          </select>
        )}
        <input type="date" value={dateFrom} onChange={e => { setDateFrom(e.target.value); setPage(1); }}
          className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
        <input type="date" value={dateTo} onChange={e => { setDateTo(e.target.value); setPage(1); }}
          className="bg-[#f3f4f5] border-none rounded-full py-2 px-4 text-sm text-[#3f4948] focus:outline-none focus:ring-2 focus:ring-[#004242]/20" />
      </div>

      {/* Table */}
      <div className="bg-white rounded-[0.5rem] overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center h-48">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
          </div>
        ) : bookings.length === 0 ? (
          <div className="text-center py-16 text-[#6f7979]">
            <span className="material-symbols-outlined text-4xl block mb-2">calendar_month</span>
            No bookings found
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e1e3e4] bg-[#f3f4f5]">
                  {['Customer', 'Salon', 'Service', 'Date & Time', 'Status', 'WhatsApp', 'Actions'].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs font-bold text-[#6f7979] uppercase tracking-widest whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {bookings.map(b => (
                  <tr key={b.id} className="border-b border-[#f3f4f5] hover:bg-[#f8f9fa] transition-colors">
                    <td className="px-5 py-4">
                      <p className="font-semibold text-[#191c1d]">{b.name}</p>
                      <p className="text-xs text-[#6f7979]">{b.email}</p>
                      {b.phone && (
                        <a href={`https://wa.me/${b.phone.replace(/\D/g,'')}`} target="_blank" rel="noreferrer"
                          className="text-xs text-[#6f7979] font-medium hover:text-[#3f4948] hover:underline">{b.phone}</a>
                      )}
                    </td>
                    <td className="px-5 py-4 text-[#3f4948] whitespace-nowrap">{b.salon?.name ?? '—'}</td>
                    <td className="px-5 py-4">
                      <p className="text-[#3f4948]">{b.service?.title ?? '—'}</p>
                      {b.service?.price && (
                        <p className="text-xs text-[#6f7979]">LKR {Number(b.service.price).toLocaleString()}</p>
                      )}
                    </td>
                    <td className="px-5 py-4 whitespace-nowrap">
                      <p className="font-medium text-[#191c1d]">
                        {b.date ? new Date(b.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' }) : '—'}
                      </p>
                      <p className="text-xs text-[#6f7979]">
                        {b.time ? (() => { const [h, m] = b.time.split(':'); const d = new Date(); d.setHours(+h, +m); return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }); })() : ''}
                      </p>
                    </td>
                    <td className="px-5 py-4"><StatusBadge status={b.status} /></td>
                    <td className="px-5 py-4">
                      {b.phone ? (
                        <button
                          onClick={() => openWhatsApp(b, b.status)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#25d366]/10 text-[#128c3e] hover:bg-[#25d366]/20 transition-colors text-xs font-semibold"
                          title="Send WhatsApp message"
                        >
                          <svg viewBox="0 0 24 24" className="w-3.5 h-3.5 fill-current" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                          </svg>
                          Send
                        </button>
                      ) : <span className="text-xs text-[#6f7979]">—</span>}
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-1 flex-wrap">
                        {/* Quick status buttons */}
                        {b.status === 'pending' && (
                          <>
                            <button onClick={() => statusMut.mutate({ id: b.id, status: 'approved' })}
                              className="p-1.5 rounded-lg bg-[#004242]/10 text-[#004242] hover:bg-[#004242]/20 transition-colors" title="Approve">
                              <span className="material-symbols-outlined text-base">check</span>
                            </button>
                            <button onClick={() => statusMut.mutate({ id: b.id, status: 'rejected' })}
                              className="p-1.5 rounded-lg bg-[#ffdad6] text-[#ba1a1a] hover:bg-[#ba1a1a]/20 transition-colors" title="Reject">
                              <span className="material-symbols-outlined text-base">close</span>
                            </button>
                          </>
                        )}
                        {b.status === 'approved' && (
                          <button onClick={() => statusMut.mutate({ id: b.id, status: 'completed' })}
                            className="p-1.5 rounded-lg bg-[#476363]/10 text-[#476363] hover:bg-[#476363]/20 transition-colors" title="Mark Completed">
                            <span className="material-symbols-outlined text-base">task_alt</span>
                          </button>
                        )}
                        {/* Edit */}
                        <button onClick={() => setModal(b)}
                          className="p-1.5 rounded-lg text-[#6f7979] hover:bg-[#f3f4f5] hover:text-[#004242] transition-colors" title="Edit">
                          <span className="material-symbols-outlined text-base">edit</span>
                        </button>
                        {/* Delete */}
                        {deleteId === b.id ? (
                          <div className="flex items-center gap-1 bg-[#ffdad6] rounded-lg px-2 py-1">
                            <span className="text-xs font-semibold text-[#ba1a1a]">Delete?</span>
                            <button onClick={() => deleteMut.mutate(b.id)} disabled={deleteMut.isPending}
                              className="text-xs font-bold text-[#ba1a1a] hover:underline px-1">Yes</button>
                            <button onClick={() => setDeleteId(null)} className="text-xs font-bold text-[#6f7979] hover:underline px-1">No</button>
                          </div>
                        ) : (
                          <button onClick={() => setDeleteId(b.id)}
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

      {modal && (
        <BookingModal
          initial={modal === 'create' ? null : modal}
          salons={salons}
          onClose={() => setModal(null)}
          onSaved={handleSaved}
        />
      )}
    </div>
  );
}
