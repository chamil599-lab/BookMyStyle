import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../utils/api';

export default function AdminSalons() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const params = new URLSearchParams({ page });
  if (search) params.set('search', search);

  const { data, isLoading } = useQuery({
    queryKey: ['admin-salons', search, page],
    queryFn: () => api.get(`/admin/salons?${params}`).then(r => r.data),
    keepPreviousData: true,
  });

  const salons = data?.data ?? [];
  const meta = data ?? {};

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="font-headline text-2xl font-bold text-[#004242]">Salons</h1>
        <span className="text-sm text-[#6f7979]">{meta.total ?? 0} total</span>
      </div>

      {/* Search */}
      <div className="bg-white rounded-[0.5rem] p-4">
        <div className="relative max-w-md">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#6f7979] text-base">search</span>
          <input
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search name, location, owner…"
            className="w-full bg-[#f3f4f5] border-none rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#004242]/20"
          />
        </div>
      </div>

      {/* Grid */}
      {isLoading ? (
        <div className="flex items-center justify-center h-48">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#004242]" />
        </div>
      ) : salons.length === 0 ? (
        <div className="bg-white rounded-[0.5rem] text-center py-16 text-[#6f7979]">
          <span className="material-symbols-outlined text-4xl block mb-2">store</span>
          No salons found
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            {salons.map(salon => (
              <div key={salon.id} className="rounded-[0.5rem] overflow-hidden border border-[#004242]/10 bg-[linear-gradient(180deg,#ffffff_0%,#f4fbfa_100%)] shadow-[0_16px_36px_rgba(0,66,66,0.08)] transition-shadow hover:shadow-[0_20px_42px_rgba(0,66,66,0.12)]">
                {/* Cover */}
                <div className="h-32 bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)] flex items-center justify-center relative overflow-hidden">
                  {salon.cover_url ? (
                    <>
                      <img src={salon.cover_url} alt={salon.name} className="w-full h-full object-cover opacity-85" />
                      <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,66,66,0.08)_0%,rgba(0,66,66,0.65)_100%)]" />
                    </>
                  ) : (
                    <span className="material-symbols-outlined text-5xl text-white/30">storefront</span>
                  )}
                  <div className="absolute -bottom-8 -right-6 h-24 w-24 rounded-full bg-white/10" />
                  <div className="absolute top-3 left-3 rounded-full bg-white/12 px-2.5 py-1 text-[9px] font-bold uppercase tracking-[0.22em] text-[#d6f1f0]">
                    Salon
                  </div>
                  <div className="absolute bottom-3 left-3">
                    <span className="text-[10px] bg-white/92 backdrop-blur-sm text-[#004242] px-2.5 py-1 rounded-full font-bold shadow-sm">
                      {salon.number_of_staff ?? 1} staff
                    </span>
                  </div>
                </div>

                <div className="p-5">
                  <h3 className="font-headline font-bold text-[#033737] truncate">{salon.name}</h3>
                  {salon.owner && (
                    <p className="text-xs text-[#547170] mt-0.5">Owner: {salon.owner.name}</p>
                  )}

                  <div className="mt-4 space-y-2">
                    {salon.location && (
                      <div className="flex items-center gap-2 text-xs text-[#285252]">
                        <span className="material-symbols-outlined text-sm text-[#004242]">location_on</span>
                        {salon.location}
                      </div>
                    )}
                    {salon.phone && (
                      <div className="flex items-center gap-2 text-xs text-[#285252]">
                        <span className="material-symbols-outlined text-sm text-[#004242]">phone</span>
                        {salon.phone}
                      </div>
                    )}
                    {(salon.open_time || salon.close_time) && (
                      <div className="flex items-center gap-2 text-xs text-[#285252]">
                        <span className="material-symbols-outlined text-sm text-[#004242]">schedule</span>
                        {salon.open_time} – {salon.close_time}
                      </div>
                    )}
                  </div>

                  <div className="mt-4 flex gap-3">
                    <div className="flex-1 rounded-xl p-2.5 text-center bg-[linear-gradient(135deg,#004242_0%,#0f5f60_100%)] shadow-[0_10px_24px_rgba(0,66,66,0.14)]">
                      <p className="font-headline font-extrabold text-white text-lg">{salon.bookings_count ?? 0}</p>
                      <p className="text-[10px] text-[#b9ecea] uppercase tracking-widest font-semibold">Bookings</p>
                    </div>
                    <div className="flex-1 rounded-xl p-2.5 text-center bg-[linear-gradient(135deg,#0c5455_0%,#2d7a78_100%)] shadow-[0_10px_24px_rgba(12,84,85,0.12)]">
                      <p className="font-headline font-extrabold text-white text-lg">{salon.services_count ?? 0}</p>
                      <p className="text-[10px] text-[#c9f3f0] uppercase tracking-widest font-semibold">Services</p>
                    </div>
                    <div className="flex-1 rounded-xl p-2.5 text-center bg-[linear-gradient(135deg,#1b6869_0%,#48908f_100%)] shadow-[0_10px_24px_rgba(27,104,105,0.12)]">
                      <p className="font-headline font-extrabold text-white text-lg">{salon.number_of_staff ?? 1}</p>
                      <p className="text-[10px] text-[#d6f7f5] uppercase tracking-widest font-semibold">Staff</p>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Pagination */}
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
    </div>
  );
}
