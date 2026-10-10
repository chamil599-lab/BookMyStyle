import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { FaFacebookF, FaInstagram, FaTiktok } from 'react-icons/fa6';
import api from '../../utils/api';

const EMPTY = {
  name: '', owner_name: '', location: '', phone: '', email: '',
  description: '', open_time: '', close_time: '',
  number_of_staff: '', show_price: true,
  facebook_page: '', instagram_page: '', tiktok_page: '',
};

function salonToForm(initial) {
  if (!initial) return EMPTY;

  return {
    name: initial.name ?? '',
    owner_name: initial.owner_name ?? '',
    location: initial.location ?? '',
    phone: initial.phone ?? '',
    email: initial.email ?? '',
    description: initial.description ?? '',
    open_time: initial.open_time ?? '',
    close_time: initial.close_time ?? '',
    number_of_staff: initial.number_of_staff ?? '',
    show_price: initial.show_price ?? true,
    facebook_page: initial.facebook_page ?? '',
    instagram_page: initial.instagram_page ?? '',
    tiktok_page: initial.tiktok_page ?? '',
  };
}

function buildSalonFormData(form, options = {}) {
  const fd = new FormData();
  const {
    coverFile = null,
    galleryFiles = [],
    removeCover = false,
    removeGalleryIds = [],
    isEdit = false,
  } = options;

  Object.entries(form).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    fd.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
  });

  if (coverFile) {
    fd.append('cover', coverFile);
  } else if (removeCover) {
    fd.append('remove_cover', '1');
  }

  galleryFiles.forEach((file) => fd.append('gallery[]', file));
  removeGalleryIds.forEach((id) => fd.append('remove_gallery_ids[]', String(id)));

  if (isEdit) {
    fd.append('_method', 'PUT');
  }

  return fd;
}

function SalonModal({ initial, onClose, onSaved }) {
  const [form, setForm]   = useState(salonToForm(initial));
  const [error, setError] = useState('');
  const [coverFile, setCoverFile] = useState(null);
  const [coverPreview, setCoverPreview] = useState(initial?.cover_url ?? '');
  const [removeCover, setRemoveCover] = useState(false);
  const [existingGallery, setExistingGallery] = useState(initial?.gallery ?? []);
  const [galleryFiles, setGalleryFiles] = useState([]);
  const [removeGalleryIds, setRemoveGalleryIds] = useState([]);
  const isEdit = !!initial?.id;

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const { mutate, isPending } = useMutation({
    mutationFn: data => isEdit
      ? api.post(`/owner/salons/${initial.id}`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        }).then(r => r.data)
      : api.post('/owner/salons', data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        }).then(r => r.data),
    onSuccess: saved => { onSaved(saved); onClose(); },
    onError: e => setError(e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ') ?? 'An error occurred'),
  });

  const handleSubmit = e => {
    e.preventDefault();
    setError('');
    mutate(buildSalonFormData(form, {
      coverFile,
      galleryFiles,
      removeCover,
      removeGalleryIds,
      isEdit,
    }));
  };

  const inp = 'w-full bg-[#f3f4f5] border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-[#6f7979] focus:outline-none focus:border-[#004242]/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-[#6f7979] uppercase tracking-widest mb-1.5';
  const coverSrc = coverPreview || initial?.cover_url || '';
  const galleryPreviewItems = [
    ...existingGallery.map((img) => ({
      key: `existing-${img.id}`,
      url: img.preview || img.original || img.thumb,
      existing: true,
      id: img.id,
    })),
    ...galleryFiles.map((file, index) => ({
      key: `new-${index}-${file.name}`,
      url: URL.createObjectURL(file),
      existing: false,
      fileIndex: index,
    })),
  ];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-lg font-bold text-[#191c1d]">{isEdit ? 'Edit Salon' : 'Add New Salon'}</h2>
          <button onClick={onClose} className="p-2 rounded-full hover:bg-[#f3f4f5] transition-colors text-[#6f7979]">
            <span className="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit} className="overflow-y-auto flex-1">
          <div className="px-6 py-5 space-y-5">
            {error && (
              <div className="bg-[#ffdad6] text-[#ba1a1a] text-sm font-medium px-4 py-3 rounded-xl border-l-4 border-[#ba1a1a]">
                {error}
              </div>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Salon Name *</label>
                <input value={form.name} onChange={e => set('name', e.target.value)} required className={inp} placeholder="e.g. Glamour Studio" />
              </div>
              <div>
                <label className={lbl}>Owner Name *</label>
                <input value={form.owner_name} onChange={e => set('owner_name', e.target.value)} required className={inp} placeholder="e.g. Jane Doe" />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={lbl}>Phone</label>
                <input value={form.phone} onChange={e => set('phone', e.target.value)} className={inp} placeholder="+94 77 123 4567" />
              </div>
              <div>
                <label className={lbl}>Email</label>
                <input type="email" value={form.email} onChange={e => set('email', e.target.value)} className={inp} placeholder="salon@example.com" />
              </div>
            </div>

            <div>
              <label className={lbl}>Location</label>
              <input value={form.location} onChange={e => set('location', e.target.value)} className={inp} placeholder="City, Address" />
            </div>

            <div>
              <label className={lbl}>Description</label>
              <textarea value={form.description} onChange={e => set('description', e.target.value)} rows={3} className={`${inp} resize-none`} placeholder="Brief description of the salon…" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
              <div>
                <label className={lbl}>Cover Image</label>
                <div className="rounded-2xl border border-dashed border-[#bec8c8] bg-[#f8f9fa] p-4">
                  <div className="h-40 rounded-xl overflow-hidden bg-[#e1e3e4] mb-3 flex items-center justify-center">
                    {coverSrc && !removeCover ? (
                      <img src={coverSrc} alt="Salon cover preview" className="w-full h-full object-cover" />
                    ) : (
                      <span className="material-symbols-outlined text-5xl text-[#6f7979]/40">image</span>
                    )}
                  </div>
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    onChange={(e) => {
                      const file = e.target.files?.[0] ?? null;
                      setCoverFile(file);
                      setRemoveCover(false);
                      setCoverPreview(file ? URL.createObjectURL(file) : (initial?.cover_url ?? ''));
                    }}
                    className="block w-full text-sm text-[#3f4948] file:mr-3 file:rounded-xl file:border-0 file:bg-[#004242] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-[#005b5c]"
                  />
                  <p className="mt-2 text-xs text-[#6f7979]">16:9 recommended. Max 1MB. JPG, PNG, or WebP.</p>
                  {(coverSrc || initial?.cover_url) && !removeCover && (
                    <button
                      type="button"
                      onClick={() => {
                        setCoverFile(null);
                        setCoverPreview('');
                        setRemoveCover(true);
                      }}
                      className="mt-3 text-xs font-bold text-[#ba1a1a] hover:underline"
                    >
                      Remove cover image
                    </button>
                  )}
                </div>
              </div>

              <div>
                <label className={lbl}>Gallery Images</label>
                <div className="rounded-2xl border border-dashed border-[#bec8c8] bg-[#f8f9fa] p-4">
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    multiple
                    onChange={(e) => {
                      const files = Array.from(e.target.files ?? []);
                      if (!files.length) return;
                      setGalleryFiles((current) => [...current, ...files].slice(0, 10));
                    }}
                    className="block w-full text-sm text-[#3f4948] file:mr-3 file:rounded-xl file:border-0 file:bg-[#476363] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-[#5b7877]"
                  />
                  <p className="mt-2 text-xs text-[#6f7979]">Up to 10 images total. Max 1MB each. JPG, PNG, or WebP.</p>

                  {galleryPreviewItems.length > 0 ? (
                    <div className="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                      {galleryPreviewItems.map((img) => (
                        <div key={img.key} className="relative rounded-xl overflow-hidden bg-[#e1e3e4] aspect-[4/3]">
                          <img src={img.url} alt="Salon gallery preview" className="w-full h-full object-cover" />
                          <button
                            type="button"
                            onClick={() => {
                              if (img.existing) {
                                setExistingGallery((current) => current.filter((item) => item.id !== img.id));
                                setRemoveGalleryIds((current) => [...current, img.id]);
                                return;
                              }

                              setGalleryFiles((current) => current.filter((_, index) => index !== img.fileIndex));
                            }}
                            className="absolute top-2 right-2 rounded-full bg-black/65 px-2 py-1 text-[10px] font-bold text-white"
                          >
                            Remove
                          </button>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="mt-4 rounded-xl bg-white px-4 py-6 text-center text-sm text-[#6f7979]">
                      No gallery images selected yet.
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label className={lbl}>Open Time</label>
                <input type="time" value={form.open_time} onChange={e => set('open_time', e.target.value)} className={inp} />
              </div>
              <div>
                <label className={lbl}>Close Time</label>
                <input type="time" value={form.close_time} onChange={e => set('close_time', e.target.value)} className={inp} />
              </div>
              <div>
                <label className={lbl}>Staff Count</label>
                <input type="number" min="0" value={form.number_of_staff} onChange={e => set('number_of_staff', e.target.value)} className={inp} placeholder="0" />
              </div>
              <div className="flex flex-col justify-end pb-1">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" checked={!!form.show_price} onChange={e => set('show_price', e.target.checked)}
                    className="w-4 h-4 accent-[#004242]" />
                  <span className="text-sm font-medium text-[#3f4948]">Show Prices</span>
                </label>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label className={lbl}>Facebook</label>
                <input value={form.facebook_page} onChange={e => set('facebook_page', e.target.value)} className={inp} placeholder="https://facebook.com/…" />
              </div>
              <div>
                <label className={lbl}>Instagram</label>
                <input value={form.instagram_page} onChange={e => set('instagram_page', e.target.value)} className={inp} placeholder="https://instagram.com/…" />
              </div>
              <div>
                <label className={lbl}>TikTok</label>
                <input value={form.tiktok_page} onChange={e => set('tiktok_page', e.target.value)} className={inp} placeholder="https://tiktok.com/@…" />
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t border-[#e1e3e4] flex items-center justify-end gap-3">
            <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl border border-[#e1e3e4] text-sm font-semibold text-[#3f4948] hover:bg-[#f3f4f5] transition-colors">
              Cancel
            </button>
            <button type="submit" disabled={isPending}
              className="px-6 py-2.5 rounded-xl bg-[#004242] text-white text-sm font-bold hover:bg-[#005b5c] transition-colors disabled:opacity-60 flex items-center gap-2">
              {isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              {isEdit ? 'Save Changes' : 'Create Salon'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function OwnerSalons() {
  const qc = useQueryClient();
  const [modal, setModal]       = useState(null);
  const [deleteId, setDeleteId] = useState(null);

  const { data: salons = [], isLoading } = useQuery({
    queryKey: ['owner-salons-full'],
    queryFn: () => api.get('/owner/salons').then(r => r.data),
  });

  const { data: limits } = useQuery({
    queryKey: ['owner-limits'],
    queryFn: () => api.get('/owner/limits').then(r => r.data),
  });

  const salonUsed = limits?.salons?.used ?? salons.length;
  const salonMax  = limits?.salons?.max ?? null;
  const atLimit   = salonMax !== null && salonUsed >= salonMax;

  const deleteMut = useMutation({
    mutationFn: id => api.delete(`/owner/salons/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['owner-salons-full'] }); setDeleteId(null); },
  });

  const handleSaved = () => qc.invalidateQueries({ queryKey: ['owner-salons-full'] });

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-headline text-2xl font-bold text-[#004242]">My Salons</h1>
          {salonMax !== null && (
            <p className="text-xs text-[#6f7979] mt-0.5 font-medium">
              {salonUsed} / {salonMax} salons used
              {atLimit && <span className="ml-2 text-[#ba1a1a] font-bold">— limit reached</span>}
            </p>
          )}
        </div>
        <button
          onClick={() => !atLimit && setModal('create')}
          disabled={atLimit}
          title={atLimit ? `Your plan allows a maximum of ${salonMax} salon(s). Upgrade to add more.` : undefined}
          className={`flex items-center gap-2 px-5 py-2.5 text-sm font-bold rounded-xl transition-colors ${
            atLimit
              ? 'bg-[#e1e3e4] text-[#6f7979] cursor-not-allowed'
              : 'bg-[#004242] text-white hover:bg-[#005b5c]'
          }`}>
          <span className="material-symbols-outlined text-base">{atLimit ? 'lock' : 'add'}</span>
          Add Salon
        </button>
      </div>

      {atLimit && (
        <div className="bg-[#fff3e0] border border-[#f4a261]/40 rounded-xl px-4 py-3 flex items-center gap-3">
          <span className="material-symbols-outlined text-[#e65100]">info</span>
          <p className="text-sm text-[#e65100] font-medium">
            You've reached your plan limit of <strong>{salonMax}</strong> salon(s).
            <a href="/owner/subscription" className="ml-1 font-bold underline">Upgrade your plan</a> to add more.
          </p>
        </div>
      )}

      {isLoading ? (
        <div className="flex items-center justify-center h-48">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
        </div>
      ) : salons.length === 0 ? (
        <div className="bg-white rounded-[0.5rem] text-center py-16 text-[#6f7979]">
          <span className="material-symbols-outlined text-5xl block mb-3">storefront</span>
          <p className="font-semibold">No salons yet</p>
          <p className="text-sm mt-1 mb-5">Create your first salon to get started.</p>
          <button onClick={() => setModal('create')}
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-[#004242] text-white text-sm font-bold rounded-xl hover:bg-[#005b5c] transition-colors">
            <span className="material-symbols-outlined text-base">add</span>
            Add Salon
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {salons.map(salon => (
            <div key={salon.id} className="rounded-[1rem] overflow-hidden border border-[#004242]/10 bg-[linear-gradient(180deg,#ffffff_0%,#f4fbfa_100%)] shadow-[0_18px_44px_rgba(0,66,66,0.08)]">
              {/* Cover */}
              <div className="h-44 bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)] relative overflow-hidden">
                {salon.cover_url ? (
                  <>
                    <img src={salon.cover_url} alt={salon.name} className="w-full h-full object-cover opacity-85" />
                    <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,66,66,0.06)_0%,rgba(0,66,66,0.72)_100%)]" />
                  </>
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <span className="material-symbols-outlined text-6xl text-white/30">storefront</span>
                  </div>
                )}
                <div className="absolute -bottom-10 -right-6 h-28 w-28 rounded-full bg-white/10" />
                <div className="absolute top-4 left-4 rounded-full bg-white/12 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-[#d6f1f0]">
                  Salon
                </div>
                <div className="absolute bottom-3 left-4">
                  <span className="text-xs bg-white/92 backdrop-blur-sm text-[#004242] px-3 py-1 rounded-full font-bold shadow-sm">
                    {salon.number_of_staff ?? 0} staff
                  </span>
                </div>
                {/* Action buttons */}
                <div className="absolute top-3 right-3 flex gap-1.5">
                  <button onClick={() => setModal(salon)}
                    className="p-1.5 rounded-lg bg-white/90 text-[#004242] hover:bg-white shadow-sm transition-colors" title="Edit">
                    <span className="material-symbols-outlined text-base">edit</span>
                  </button>
                  {deleteId === salon.id ? (
                    <div className="flex items-center gap-1 bg-white/95 rounded-lg px-2 py-1 shadow-sm">
                      <span className="text-xs font-semibold text-[#ba1a1a]">Delete?</span>
                      <button onClick={() => deleteMut.mutate(salon.id)} disabled={deleteMut.isPending}
                        className="text-xs font-bold text-[#ba1a1a] hover:underline px-1">Yes</button>
                      <button onClick={() => setDeleteId(null)} className="text-xs font-bold text-[#6f7979] hover:underline px-1">No</button>
                    </div>
                  ) : (
                    <button onClick={() => setDeleteId(salon.id)}
                      className="p-1.5 rounded-lg bg-white/90 text-[#ba1a1a] hover:bg-white shadow-sm transition-colors" title="Delete">
                      <span className="material-symbols-outlined text-base">delete</span>
                    </button>
                  )}
                </div>
              </div>

              <div className="p-5">
                <h3 className="font-headline text-lg font-bold text-[#033737]">{salon.name}</h3>
                {salon.description && (
                  <p className="text-sm text-[#547170] mt-1 line-clamp-2">{salon.description}</p>
                )}

                <div className="mt-4 space-y-2">
                  {salon.location && (
                    <div className="flex items-center gap-2 text-sm text-[#285252]">
                      <span className="material-symbols-outlined text-base text-[#004242]">location_on</span>
                      {salon.location}
                    </div>
                  )}
                  {salon.phone && (
                    <div className="flex items-center gap-2 text-sm text-[#285252]">
                      <span className="material-symbols-outlined text-base text-[#004242]">phone</span>
                      {salon.phone}
                    </div>
                  )}
                  {salon.email && (
                    <div className="flex items-center gap-2 text-sm text-[#285252]">
                      <span className="material-symbols-outlined text-base text-[#004242]">mail</span>
                      {salon.email}
                    </div>
                  )}
                  {(salon.open_time || salon.close_time) && (
                    <div className="flex items-center gap-2 text-sm text-[#285252]">
                      <span className="material-symbols-outlined text-base text-[#004242]">schedule</span>
                      {salon.open_time} – {salon.close_time}
                    </div>
                  )}
                </div>

                {(salon.facebook_page || salon.instagram_page || salon.tiktok_page) && (
                  <div className="mt-3 flex gap-2">
                    {salon.facebook_page && (
                      <a href={salon.facebook_page} target="_blank" rel="noreferrer"
                        className="p-2 rounded-lg bg-[#f3f4f5] text-[#3f4948] hover:bg-[#004242]/10 hover:text-[#004242] transition-colors">
                        <FaFacebookF className="text-[15px]" />
                      </a>
                    )}
                    {salon.instagram_page && (
                      <a href={salon.instagram_page} target="_blank" rel="noreferrer"
                        className="p-2 rounded-lg bg-[#f3f4f5] text-[#3f4948] hover:bg-[#004242]/10 hover:text-[#004242] transition-colors">
                        <FaInstagram className="text-[15px]" />
                      </a>
                    )}
                    {salon.tiktok_page && (
                      <a href={salon.tiktok_page} target="_blank" rel="noreferrer"
                        className="p-2 rounded-lg bg-[#f3f4f5] text-[#3f4948] hover:bg-[#004242]/10 hover:text-[#004242] transition-colors">
                        <FaTiktok className="text-[15px]" />
                      </a>
                    )}
                  </div>
                )}

                <div className="mt-5 grid grid-cols-2 gap-3">
                  <div className="rounded-2xl p-3.5 text-center bg-[linear-gradient(135deg,#004242_0%,#0f5f60_100%)] shadow-[0_12px_30px_rgba(0,66,66,0.16)]">
                    <p className="font-headline text-2xl font-extrabold text-white">{salon.bookings_count ?? 0}</p>
                    <p className="text-[10px] text-[#b9ecea] uppercase tracking-widest font-semibold mt-0.5">Bookings</p>
                  </div>
                  <div className="rounded-2xl p-3.5 text-center bg-[linear-gradient(135deg,#0c5455_0%,#2d7a78_100%)] shadow-[0_12px_30px_rgba(12,84,85,0.14)]">
                    <p className="font-headline text-2xl font-extrabold text-white">{salon.services_count ?? 0}</p>
                    <p className="text-[10px] text-[#c9f3f0] uppercase tracking-widest font-semibold mt-0.5">Services</p>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {modal && (
        <SalonModal
          initial={modal === 'create' ? null : modal}
          onClose={() => setModal(null)}
          onSaved={handleSaved}
        />
      )}
    </div>
  );
}
