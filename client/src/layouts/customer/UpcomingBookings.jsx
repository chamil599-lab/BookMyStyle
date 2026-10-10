import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { format } from 'date-fns';
import api from '../../utils/api';

export default function UpcomingBookings() {
  const queryClient = useQueryClient();

  const { data: bookings, isLoading } = useQuery({
    queryKey: ['upcoming-bookings'],
    queryFn: async () => {
      const response = await api.get('/customer/bookings/upcoming');
      return response.data.data;
    },
  });

  const cancelMutation = useMutation({
    mutationFn: async (bookingId) => {
      await api.patch(`/customer/bookings/${bookingId}/cancel`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['upcoming-bookings'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard-stats'] });
    },
  });

  const handleCancel = (bookingId) => {
    if (window.confirm('Are you sure you want to cancel this booking?')) {
      cancelMutation.mutate(bookingId);
    }
  };

  const getStatusBadge = (status) => {
    const classes = {
      pending: 'bg-[#fff3e0] text-[#e65100]',
      approved: 'bg-[#e8f5e9] text-[#2e7d32]',
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
        <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#6f7979]">Upcoming Appointments</p>
        <div className="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h1 className="font-headline text-3xl font-extrabold text-[#191c1d]">Your Next Visits</h1>
            <p className="mt-2 text-sm text-[#6f7979]">Stay on top of what’s booked, approved, or still waiting for confirmation.</p>
          </div>
          <Link
            to="/salons"
            className="inline-flex items-center gap-2 rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-5 py-2.5 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition hover:translate-y-[-1px]"
          >
            <span className="material-symbols-outlined text-[18px]">add_circle</span>
            Book Another
          </Link>
        </div>
      </div>

      {!bookings || bookings.length === 0 ? (
        <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-[linear-gradient(180deg,#ffffff_0%,#f8fbfb_100%)] p-12 text-center shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_18px_34px_rgba(0,66,66,0.18)]">
            <span className="material-symbols-outlined text-4xl">calendar_month</span>
          </div>
          <h3 className="mt-5 font-headline text-2xl font-bold text-[#191c1d]">No Upcoming Appointments</h3>
          <p className="mx-auto mt-2 max-w-md text-sm text-[#6f7979]">
            You don&apos;t have any scheduled appointments yet. Explore salons and lock in your next visit.
          </p>
          <Link
            to="/salons"
            className="mt-6 inline-flex items-center gap-2 rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-6 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition hover:translate-y-[-1px]"
          >
            <span className="material-symbols-outlined text-[18px]">storefront</span>
            Book an Appointment
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {bookings.map((booking) => (
            <div
              key={booking.id}
              className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)] transition hover:translate-y-[-1px]"
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
                    {booking.salon?.address && (
                      <p className="flex items-center gap-2">
                        <span className="material-symbols-outlined text-[18px] text-[#004242]">location_on</span>
                        <span>{booking.salon.address}</span>
                      </p>
                    )}
                  </div>
                </div>

                {(booking.status === 'pending' || booking.status === 'approved') && (
                  <button
                    onClick={() => handleCancel(booking.id)}
                    disabled={cancelMutation.isPending}
                    className="rounded-full border border-[#f0b2ac] px-4 py-2 text-sm font-bold text-[#ba1a1a] transition hover:bg-[#fff1ef] disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {cancelMutation.isPending ? 'Cancelling...' : 'Cancel Booking'}
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
