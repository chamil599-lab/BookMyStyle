import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../utils/api';

const EMPTY_STATS = {
  total_bookings: 0,
  upcoming_bookings: 0,
  completed_bookings: 0,
  cancelled_bookings: 0,
  favorite_salons: 0,
};

export default function DashboardOverview() {
  const { data: stats = EMPTY_STATS } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const response = await api.get('/customer/stats');
      return response.data.data;
    },
    placeholderData: EMPTY_STATS,
  });

  const statCards = [
    {
      title: 'Total Bookings',
      value: stats?.total_bookings || 0,
      icon: 'insights',
      cardClass: 'bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)]',
      meta: 'All appointments',
      link: '/dashboard/history',
    },
    {
      title: 'Upcoming',
      value: stats?.upcoming_bookings || 0,
      icon: 'calendar_month',
      cardClass: 'bg-[linear-gradient(135deg,#2d5252_0%,#476363_58%,#5f7e7d_100%)]',
      meta: 'Scheduled next',
      link: '/dashboard/upcoming',
    },
    {
      title: 'Completed',
      value: stats?.completed_bookings || 0,
      icon: 'task_alt',
      cardClass: 'bg-[linear-gradient(135deg,#7a3e12_0%,#b96516_55%,#e0912d_100%)]',
      meta: 'Finished visits',
      link: '/dashboard/history',
    },
    {
      title: 'Cancelled',
      value: stats?.cancelled_bookings || 0,
      icon: 'event_busy',
      cardClass: 'bg-[linear-gradient(135deg,#7c1820_0%,#a1262d_52%,#c8464c_100%)]',
      meta: 'Ended early',
      link: '/dashboard/history',
    },
  ];

  return (
    <div className="space-y-6">
      <div className="relative overflow-hidden rounded-[1.4rem] border border-[#d7e4e3] bg-[linear-gradient(135deg,#f7fbfb_0%,#edf5f5_100%)] p-6 shadow-[0_22px_48px_rgba(25,28,29,0.08)]">
        <div className="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-[#8bd0d1]/18" />
        <div className="absolute right-20 bottom-[-26px] h-20 w-20 rounded-full bg-[#004242]/7" />
        <div className="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#6f7979]">Dashboard Overview</p>
            <h1 className="mt-2 font-headline text-3xl font-extrabold text-[#191c1d]">Your Beauty Journey</h1>
            <p className="mt-2 max-w-2xl text-sm text-[#6f7979]">
              Keep track of upcoming visits, revisit your booking history, and jump back into the salons you love.
            </p>
          </div>

          <div className="flex flex-wrap gap-3">
            <Link
              to="/salons"
              className="inline-flex items-center gap-2 rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-5 py-2.5 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.2)] transition hover:translate-y-[-1px]"
            >
              <span className="material-symbols-outlined text-[18px]">storefront</span>
              Book New Appointment
            </Link>
            <Link
              to="/dashboard/upcoming"
              className="inline-flex items-center gap-2 rounded-full border border-[#c9d8d7] bg-white px-5 py-2.5 text-sm font-bold text-[#004242] transition hover:border-[#004242]/25 hover:bg-[#f6f9f9]"
            >
              <span className="material-symbols-outlined text-[18px]">schedule</span>
              View Upcoming
            </Link>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {statCards.map((stat) => (
          <Link
            key={stat.title}
            to={stat.link}
            className={`relative overflow-hidden rounded-[1.15rem] p-5 text-white shadow-[0_20px_42px_rgba(25,28,29,0.10)] transition hover:translate-y-[-2px] ${stat.cardClass}`}
          >
            <div className="absolute -right-6 -bottom-6 h-24 w-24 rounded-full bg-white/8" />
            <div className="absolute right-4 top-4 h-14 w-14 rounded-full bg-white/8" />
            <div className="relative flex items-start justify-between gap-4">
              <div>
                <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-white/72">{stat.title}</p>
                <p className="mt-3 font-headline text-3xl font-extrabold text-white">{stat.value}</p>
                <p className="mt-2 text-xs font-semibold text-white/72">{stat.meta}</p>
              </div>
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/12">
                <span className="material-symbols-outlined text-[24px] text-white">{stat.icon}</span>
              </div>
            </div>
          </Link>
        ))}
      </div>

      <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          <div className="mb-5 flex items-center justify-between gap-3">
            <div>
              <h2 className="font-headline text-xl font-bold text-[#191c1d]">Quick Actions</h2>
              <p className="text-sm text-[#6f7979]">Jump into the most common customer tasks</p>
            </div>
            <span className="rounded-full bg-[#004242]/8 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-[#004242]">
              Fast Access
            </span>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Link
              to="/salons"
              className="rounded-[1.1rem] border border-[#d5e2e1] bg-[linear-gradient(135deg,#f8fbfb_0%,#eef6f6_100%)] p-5 transition hover:border-[#004242]/18 hover:shadow-[0_14px_28px_rgba(0,66,66,0.08)]"
            >
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_12px_24px_rgba(0,66,66,0.18)]">
                <span className="material-symbols-outlined text-[22px]">storefront</span>
              </div>
              <p className="mt-4 text-base font-bold text-[#191c1d]">Book New Appointment</p>
              <p className="mt-1 text-sm text-[#6f7979]">Browse salons and services with the latest availability.</p>
            </Link>

            <Link
              to="/dashboard/upcoming"
              className="rounded-[1.1rem] border border-[#d5e2e1] bg-[linear-gradient(135deg,#ffffff_0%,#f7f7f2_100%)] p-5 transition hover:border-[#004242]/18 hover:shadow-[0_14px_28px_rgba(0,66,66,0.08)]"
            >
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#7a3e12_0%,#e0912d_100%)] text-white shadow-[0_12px_24px_rgba(122,62,18,0.18)]">
                <span className="material-symbols-outlined text-[22px]">calendar_month</span>
              </div>
              <p className="mt-4 text-base font-bold text-[#191c1d]">Manage Upcoming</p>
              <p className="mt-1 text-sm text-[#6f7979]">Review your next appointments and make changes when needed.</p>
            </Link>
          </div>
        </div>

        <div className="rounded-[1.2rem] bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)] p-6 text-white shadow-[0_18px_42px_rgba(0,66,66,0.18)]">
          <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#8bd0d1]">Saved Favorites</p>
          <h2 className="mt-2 font-headline text-2xl font-extrabold text-white">{stats?.favorite_salons ?? 0}</h2>
          <p className="mt-3 text-sm leading-relaxed text-white/72">
            Keep your best salons close so rebooking feels quick, easy, and personal.
          </p>
          <Link
            to="/dashboard/favorites"
            className="mt-6 inline-flex items-center gap-2 rounded-full bg-white/12 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/16"
          >
            <span className="material-symbols-outlined text-[18px]">favorite</span>
            Open Favorites
          </Link>
        </div>
      </div>
    </div>
  );
}
