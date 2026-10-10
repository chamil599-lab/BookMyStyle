import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import api from '../../utils/api';

export default function BookingHistory() {
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['booking-history', page],
    queryFn: async () => {
      const response = await api.get(`/customer/bookings/history?page=${page}`);
      return response.data;
    },
  });

  const bookings = data?.data || [];

  const getStatusBadge = (status) => {
    const classes = {
      pending: 'bg-[#fff3e0] text-[#e65100]',
      approved: 'bg-[#e8f5e9] text-[#2e7d32]',
      completed: 'bg-[#e0f2f1] text-[#00695c]',
      cancelled: 'bg-[#ffdad6] text-[#ba1a1a]',
      rejected: 'bg-[#ffdad6] text-[#ba1a1a]',
    };

    return (
      <span className={`rounded-full px-3 py-1 text-xs font-bold ${classes[status] ?? 'bg-[#e1e3e4] text-[#3f4948]'}`}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <div className="h-12 w-12 animate-spin rounded-full border-b-2 border-[#004242]" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
        <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#6f7979]">Booking History</p>
        <h1 className="mt-2 font-headline text-3xl font-extrabold text-[#191c1d]">Past Visits & Records</h1>
        <p className="mt-2 text-sm text-[#6f7979]">Review previous appointments, service details, and salon visits in one timeline.</p>
      </div>

      {!bookings || bookings.length === 0 ? (
        <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-[linear-gradient(180deg,#ffffff_0%,#f8fbfb_100%)] p-12 text-center shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-[linear-gradient(135deg,#2d5252_0%,#5f7e7d_100%)] text-white shadow-[0_18px_34px_rgba(45,82,82,0.18)]">
            <span className="material-symbols-outlined text-4xl">history</span>
          </div>
          <h3 className="mt-5 font-headline text-2xl font-bold text-[#191c1d]">No Booking History</h3>
          <p className="mt-2 text-sm text-[#6f7979]">You don&apos;t have any past bookings yet.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {bookings.map((booking) => (
            <div
              key={booking.id}
              className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]"
            >
              <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex-1">
                  <div className="mb-2 flex items-center gap-3">
                    <h3 className="font-headline text-xl font-bold text-[#191c1d]">{booking.salon?.name || 'Salon unavailable'}</h3>
                    {getStatusBadge(booking.status)}
                  </div>

                  <div className="grid gap-2 text-sm text-[#6f7979] md:grid-cols-2">
                    <p className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-[18px] text-[#004242]">content_cut</span>
                      <span className="font-medium">{booking.service?.name || 'Service unavailable'}</span>
                      {booking.service?.price && <span className="font-semibold text-[#004242]">LKR {booking.service.price}/=</span>}
                    </p>
                    <p className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-[18px] text-[#004242]">calendar_month</span>
                      <span>{format(new Date(booking.date), 'MMMM d, yyyy')}</span>
                    </p>
                    <p className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-[18px] text-[#004242]">schedule</span>
                      <span>{booking.time}</span>
                      {booking.service?.duration && <span>&bull; {booking.service.duration} mins</span>}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          ))}

          {data?.meta && data.meta.last_page > 1 && (
            <div className="mt-6 flex flex-wrap items-center justify-center gap-3 rounded-[1.1rem] border border-[#d7e4e3] bg-white p-4 shadow-[0_14px_32px_rgba(25,28,29,0.06)]">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={data.meta.current_page === 1}
                className="rounded-full border border-[#d0dddd] px-4 py-2 text-sm font-bold text-[#3f4948] transition hover:bg-[#f5f8f8] disabled:cursor-not-allowed disabled:opacity-50"
              >
                Previous
              </button>
              <span className="px-4 py-2 text-sm font-semibold text-[#6f7979]">
                Page {data.meta.current_page} of {data.meta.last_page}
              </span>
              <button
                onClick={() => setPage(page + 1)}
                disabled={!data.meta || data.meta.current_page >= data.meta.last_page}
                className="rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-4 py-2 text-sm font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
              >
                Next
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
