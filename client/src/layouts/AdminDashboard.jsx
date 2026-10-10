import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import api from '../utils/api';

function fmt(n) {
  return `LKR ${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function fmtShort(n) {
  const v = Number(n ?? 0);
  if (v >= 1_000_000) return `LKR ${(v / 1_000_000).toFixed(1)}M`;
  if (v >= 1_000) return `LKR ${(v / 1_000).toFixed(1)}K`;
  return `LKR ${v.toFixed(0)}`;
}

const STATUS_CONFIG = [
  { key: 'active',   label: 'Active',   color: 'bg-[#004242]',       dot: 'bg-[#004242]' },
  { key: 'pending',  label: 'Pending',  color: 'bg-[#5d2e10]',       dot: 'bg-[#5d2e10]' },
  { key: 'expired',  label: 'Expired',  color: 'bg-[#ba1a1a]',       dot: 'bg-[#ba1a1a]' },
  { key: 'canceled', label: 'Canceled', color: 'bg-[#bec8c8]',       dot: 'bg-[#bec8c8]' },
];

const PACKAGE_ICONS = ['diamond', 'star', 'workspace_premium', 'bolt', 'local_fire_department'];
const PACKAGE_BG    = ['bg-[#ffdbca]', 'bg-[#c7e6e5]', 'bg-[#a8efef]/30', 'bg-[#e1e3e4]', 'bg-[#ffdad6]'];
const PACKAGE_ICON_COLOR = ['text-[#5d2e10]', 'text-[#476363]', 'text-[#004242]', 'text-[#3f4948]', 'text-[#ba1a1a]'];

export default function AdminDashboard() {
  const [year, setYear] = useState(new Date().getFullYear());
  const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

  const QUERY_OPTS = { staleTime: 60_000, refetchOnWindowFocus: false };

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['admin-stats'],
    queryFn: () => api.get('/admin/dashboard/stats').then((r) => r.data),
    ...QUERY_OPTS,
  });

  const { data: revenueChart = [], isLoading: chartLoading } = useQuery({
    queryKey: ['admin-revenue-chart', year],
    queryFn: () => api.get(`/admin/dashboard/revenue-chart?year=${year}`).then((r) => r.data),
    ...QUERY_OPTS,
  });

  const { data: visitsChart = [], isLoading: visitsChartLoading } = useQuery({
    queryKey: ['admin-visits-chart', year],
    queryFn: () => api.get(`/admin/dashboard/visits-chart?year=${year}`).then((r) => r.data),
    ...QUERY_OPTS,
  });

  const { data: subChart = [] } = useQuery({
    queryKey: ['admin-sub-chart'],
    queryFn: () => api.get('/admin/dashboard/subscription-chart').then((r) => r.data),
    ...QUERY_OPTS,
  });

  const { data: topPackages = [] } = useQuery({
    queryKey: ['admin-top-packages'],
    queryFn: () => api.get('/admin/dashboard/top-packages').then((r) => r.data),
    ...QUERY_OPTS,
  });

  const { data: packages = [] } = useQuery({
    queryKey: ['admin-packages'],
    queryFn: () => api.get('/admin/packages').then((r) => r.data),
    ...QUERY_OPTS,
  });

  if (statsLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-[#004242]" />
      </div>
    );
  }

  const subTotal = subChart.reduce((s, d) => s + (d.count ?? 0), 0);

  return (
    <div className="space-y-8">
      {/* ── Bento stat cards ── */}
      <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        {/* Subscription Revenue */}
        <div className="bg-[#1e293b] border border-[#334155] p-6 rounded-[0.5rem] relative overflow-hidden">
          <div className="absolute top-0 left-0 w-1 h-full bg-[#16a34a]" />
          <p className="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-2">Subscription Revenue</p>
          <h3 className="font-headline text-3xl font-extrabold text-white">
            {fmtShort(stats?.revenue?.subscription_total)}
          </h3>
          <div className="mt-4 flex items-center text-xs text-[#16a34a] font-bold">
            <span className="material-symbols-outlined text-sm mr-1">trending_up</span>
            <span>Total earned</span>
          </div>
        </div>

        {/* Active Subscriptions */}
        <div className="bg-[#1e293b] border border-[#334155] p-6 rounded-[0.5rem] relative">
          <p className="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-2">Active Subscriptions</p>
          <h3 className="font-headline text-3xl font-extrabold text-white">
            {stats?.subscriptions?.active ?? 0}
          </h3>
          <div className="mt-4 flex items-center text-xs text-[#cbd5e1] font-medium">
            <span className="material-symbols-outlined text-sm mr-1">sync</span>
            <span>{stats?.subscriptions?.pending ?? 0} pending</span>
          </div>
        </div>

        {/* Pending Approvals */}
        <div className="bg-[#1e293b] border border-[#334155] p-6 rounded-[0.5rem] relative">
          <p className="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-2">Pending Approvals</p>
          <h3 className="font-headline text-3xl font-extrabold text-white">
            {stats?.users?.pending_approval ?? 0}
          </h3>
          <div className="mt-4">
            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${
              (stats?.users?.pending_approval ?? 0) > 0
                ? 'bg-[#ba1a1a]/20 text-[#fca5a5]'
                : 'bg-[#16a34a]/20 text-[#86efac]'
            }`}>
              {(stats?.users?.pending_approval ?? 0) > 0 ? 'Needs action' : 'All clear'}
            </span>
          </div>
        </div>

        {/* Total Bookings */}
        <div className="bg-[#1e293b] border border-[#334155] p-6 rounded-[0.5rem] relative">
          <p className="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-2">Total Bookings</p>
          <h3 className="font-headline text-3xl font-extrabold text-white">
            {stats?.bookings?.total ?? 0}
          </h3>
          <div className="mt-4 flex items-center text-xs font-medium text-[#cbd5e1]">
            <span className="material-symbols-outlined text-sm mr-1">calendar_today</span>
            <span>{stats?.bookings?.this_month ?? 0} this month</span>
          </div>
        </div>

        {/* Site Visits */}
        <div className="bg-[#1e293b] border border-[#334155] p-6 rounded-[0.5rem] relative">
          <p className="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-2">Site Visits</p>
          <h3 className="font-headline text-3xl font-extrabold text-white">
            {Number(stats?.visits?.total ?? 0).toLocaleString()}
          </h3>
          <div className="mt-4 flex items-center text-xs font-medium text-[#cbd5e1]">
            <span className="material-symbols-outlined text-sm mr-1">visibility</span>
            <span>{Number(stats?.visits?.this_month ?? 0).toLocaleString()} this month</span>
          </div>
        </div>
      </section>

      {/* ── Main 3-col grid ── */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left: chart + packages */}
        <div className="lg:col-span-2 space-y-8">
          {/* Revenue chart */}
          <div className="bg-[#1e293b] border border-[#334155] p-8 rounded-[0.5rem]">
            <div className="flex justify-between items-end mb-8">
              <div>
                <h2 className="font-headline text-lg font-bold text-white">Booking Revenue</h2>
                <p className="text-sm text-[#94a3b8]">Monthly overview · {year}</p>
              </div>
              <select
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
                className="text-xs border border-[#334155] bg-[#0f172a] rounded-full px-3 py-1.5 font-semibold text-[#cbd5e1] focus:outline-none focus:ring-2 focus:ring-[#16a34a]/30"
              >
                {years.map((y) => <option key={y} value={y}>{y}</option>)}
              </select>
            </div>

            {chartLoading ? (
              <div className="h-64 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#16a34a]" />
              </div>
            ) : (
              <ResponsiveContainer width="100%" height={256}>
                <BarChart data={revenueChart} margin={{ top: 4, right: 4, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#334155" />
                  <XAxis
                    dataKey="month"
                    tick={{ fontSize: 11, fill: '#94a3b8', fontFamily: 'Inter', fontWeight: 700, letterSpacing: '0.05em', textTransform: 'uppercase' }}
                    axisLine={false} tickLine={false}
                  />
                  <YAxis
                    tick={{ fontSize: 11, fill: '#94a3b8', fontFamily: 'Inter' }}
                    axisLine={false} tickLine={false}
                    tickFormatter={(v) => v >= 1000 ? `${(v/1000).toFixed(0)}K` : v}
                    width={40}
                  />
                  <Tooltip
                    cursor={{ fill: 'rgba(22,163,74,0.08)' }}
                    formatter={(v) => [fmt(v), 'Revenue']}
                    contentStyle={{
                      borderRadius: 8,
                      border: '1px solid #334155',
                      background: '#0f172a',
                      color: '#ffffff',
                      fontSize: 13,
                      fontFamily: 'Inter',
                    }}
                  />
                  <Bar dataKey="revenue" fill="#16a34a" radius={[4, 4, 0, 0]} maxBarSize={32} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>

          {/* Site visits chart */}
          <div className="bg-[#1e293b] border border-[#334155] p-8 rounded-[0.5rem]">
            <div className="flex justify-between items-end mb-8">
              <div>
                <h2 className="font-headline text-lg font-bold text-white">Site Visits</h2>
                <p className="text-sm text-[#94a3b8]">Monthly page visits · {year}</p>
              </div>
              <select
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
                className="text-xs border border-[#334155] bg-[#0f172a] rounded-full px-3 py-1.5 font-semibold text-[#cbd5e1] focus:outline-none focus:ring-2 focus:ring-[#16a34a]/30"
              >
                {years.map((y) => <option key={y} value={y}>{y}</option>)}
              </select>
            </div>

            {visitsChartLoading ? (
              <div className="h-64 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#16a34a]" />
              </div>
            ) : (
              <ResponsiveContainer width="100%" height={256}>
                <BarChart data={visitsChart} margin={{ top: 4, right: 4, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#334155" />
                  <XAxis
                    dataKey="month"
                    tick={{ fontSize: 11, fill: '#94a3b8', fontFamily: 'Inter', fontWeight: 700, letterSpacing: '0.05em', textTransform: 'uppercase' }}
                    axisLine={false} tickLine={false}
                  />
                  <YAxis
                    tick={{ fontSize: 11, fill: '#94a3b8', fontFamily: 'Inter' }}
                    axisLine={false} tickLine={false}
                    allowDecimals={false}
                    width={40}
                  />
                  <Tooltip
                    cursor={{ fill: 'rgba(22,163,74,0.08)' }}
                    formatter={(v) => [Number(v).toLocaleString(), 'Visits']}
                    contentStyle={{
                      borderRadius: 8,
                      border: '1px solid #334155',
                      background: '#0f172a',
                      color: '#ffffff',
                      fontSize: 13,
                      fontFamily: 'Inter',
                    }}
                  />
                  <Bar dataKey="visits" fill="#22c55e" radius={[4, 4, 0, 0]} maxBarSize={32} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>

          {/* Top packages */}
          <div className="bg-[#1e293b] border border-[#334155] rounded-[0.5rem] overflow-hidden">
            <div className="px-6 py-5 border-b border-[#334155] flex justify-between items-center">
              <h2 className="font-headline text-lg font-bold text-white">Top Subscription Packages</h2>
            </div>
            <div>
              {topPackages.length === 0 ? (
                <p className="text-center text-[#94a3b8] text-sm py-10">No packages data</p>
              ) : (
                topPackages.map((pkg, i) => (
                  <div
                    key={i}
                    className="px-6 py-5 flex items-center gap-4 hover:bg-[#334155] transition-colors border-b border-[#334155] last:border-b-0"
                  >
                    <div className={`w-14 h-14 rounded-xl ${PACKAGE_BG[i % PACKAGE_BG.length]} flex items-center justify-center flex-shrink-0`}>
                      <span className={`material-symbols-outlined text-2xl ${PACKAGE_ICON_COLOR[i % PACKAGE_ICON_COLOR.length]}`}>
                        {PACKAGE_ICONS[i % PACKAGE_ICONS.length]}
                      </span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-bold text-white truncate">{pkg.name}</p>
                      <p className="text-xs text-[#94a3b8]">{pkg.subscriber_count} subscriber{pkg.subscriber_count !== 1 ? 's' : ''}</p>
                    </div>
                    <div className="text-right flex-shrink-0">
                      <p className="font-bold text-[#16a34a]">{fmtShort(pkg.revenue)}</p>
                      <p className="text-[10px] text-[#94a3b8] uppercase tracking-widest">Revenue</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* Right: subscription status + promo */}
        <div className="space-y-8">
          {/* Subscription status breakdown */}
          <div className="bg-[#1e293b] border border-[#334155] p-8 rounded-[0.5rem] flex flex-col gap-6">
            <h2 className="font-headline text-lg font-bold text-white">Subscription Status</h2>
            <div className="space-y-5">
              {STATUS_CONFIG.map((cfg) => {
                const item = subChart.find((d) => d.status?.toLowerCase() === cfg.key);
                const count = item?.count ?? 0;
                const pct = subTotal > 0 ? Math.round((count / subTotal) * 100) : 0;
                return (
                  <div key={cfg.key}>
                    <div className="flex items-center justify-between mb-1.5">
                      <div className="flex items-center gap-2">
                        <div className={`w-2 h-2 rounded-full ${cfg.dot}`} />
                        <span className="text-sm font-medium text-[#cbd5e1]">{cfg.label}</span>
                      </div>
                      <span className="font-bold text-sm text-white">{count}</span>
                    </div>
                    <div className="w-full bg-[#334155] h-1.5 rounded-full overflow-hidden">
                      <div
                        className={`${cfg.color} h-full rounded-full transition-all duration-700`}
                        style={{ width: `${pct}%` }}
                      />
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Mini summary */}
            <div className="mt-2 pt-4 border-t border-[#334155] grid grid-cols-2 gap-3 text-center">
              <div>
                <p className="font-headline text-xl font-extrabold text-[#16a34a]">{stats?.subscriptions?.total ?? 0}</p>
                <p className="text-[11px] text-[#94a3b8] uppercase tracking-widest font-semibold mt-0.5">Total</p>
              </div>
              <div>
                <p className="font-headline text-xl font-extrabold text-white">{stats?.users?.total_owners ?? 0}</p>
                <p className="text-[11px] text-[#94a3b8] uppercase tracking-widest font-semibold mt-0.5">Owners</p>
              </div>
            </div>
          </div>

          {/* Booking revenue today card */}
          <div className="relative bg-[#16a34a] rounded-[0.5rem] overflow-hidden h-56 group">
            <div className="absolute inset-0 bg-gradient-to-br from-[#15803d] via-[#16a34a] to-[#22c55e]" />
            <div className="absolute bottom-0 right-0 w-40 h-40 rounded-full bg-white/5 translate-x-8 translate-y-8" />
            <div className="absolute top-4 right-4 w-20 h-20 rounded-full bg-white/5 -translate-y-2" />
            <div className="relative z-10 p-6 flex flex-col h-full justify-between">
              <div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-white/80">Today's Revenue</p>
                <p className="font-headline text-3xl font-extrabold text-white mt-1">
                  {fmtShort(stats?.revenue?.today_bookings)}
                </p>
              </div>
              <div>
                <p className="text-sm text-white/80 leading-relaxed">
                  Booking revenue collected today across all salons.
                </p>
                <div className="mt-3 flex items-center gap-2 text-white text-xs font-bold">
                  <span className="material-symbols-outlined text-sm">bar_chart</span>
                  <span>Total bookings: {fmt(stats?.revenue?.booking_total)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Billing ── */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="font-headline text-lg font-bold text-white">Billing</h2>
            <p className="text-sm text-[#94a3b8]">Subscription packages and their performance</p>
          </div>
          <Link
            to="/admin/subscriptions"
            className="flex items-center gap-1.5 text-sm font-semibold text-[#16a34a] hover:underline"
          >
            View all subscriptions
            <span className="material-symbols-outlined text-base">arrow_forward</span>
          </Link>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
          {packages.length === 0 ? (
            <p className="text-sm text-[#94a3b8] col-span-full py-8 text-center">No packages found</p>
          ) : packages.map((pkg, i) => {
            const topPkg = topPackages.find(t => t.name === pkg.name);
            const features = [
              pkg.max_salons          != null && `${pkg.max_salons} salon${pkg.max_salons !== 1 ? 's' : ''}`,
              pkg.max_staff           != null && `${pkg.max_staff} staff`,
              pkg.max_services        != null && `${pkg.max_services} services`,
              pkg.max_bookings_per_month != null && `${pkg.max_bookings_per_month} bookings/mo`,
              pkg.analytics                   && 'Analytics',
              pkg.priority_support            && 'Priority support',
            ].filter(Boolean);

            return (
              <div key={pkg.id} className="bg-[#1e293b] border border-[#334155] rounded-lg overflow-hidden flex flex-col">
                {/* Card header */}
                <div className={`px-6 pt-6 pb-4 ${i === 0 ? 'bg-[#16a34a]' : 'bg-[#0f172a]'}`}>
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className={`text-[10px] font-bold uppercase tracking-widest mb-1 ${i === 0 ? 'text-white/80' : 'text-[#94a3b8]'}`}>
                        Plan
                      </p>
                      <h3 className="font-headline text-xl font-extrabold text-white">
                        {pkg.name}
                      </h3>
                    </div>
                    <div className="text-right shrink-0">
                      <p className={`font-headline text-2xl font-extrabold ${i === 0 ? 'text-white' : 'text-[#16a34a]'}`}>
                        {fmtShort(pkg.price)}
                      </p>
                      <p className={`text-[10px] font-semibold ${i === 0 ? 'text-white/70' : 'text-[#94a3b8]'}`}>
                        / {pkg.duration_days} days
                      </p>
                    </div>
                  </div>
                </div>

                {/* Stats row */}
                <div className="px-6 py-4 flex items-center gap-6 border-b border-[#334155]">
                  <div>
                    <p className="font-headline text-xl font-extrabold text-[#16a34a]">
                      {pkg.active_count ?? 0}
                    </p>
                    <p className="text-[10px] text-[#94a3b8] uppercase tracking-widest font-semibold">Active</p>
                  </div>
                  {topPkg && (
                    <div>
                      <p className="font-headline text-xl font-extrabold text-white">
                        {fmtShort(topPkg.revenue)}
                      </p>
                      <p className="text-[10px] text-[#94a3b8] uppercase tracking-widest font-semibold">Revenue</p>
                    </div>
                  )}
                </div>

                {/* Features */}
                <div className="px-6 py-4 flex-1">
                  <ul className="space-y-2">
                    {features.map(f => (
                      <li key={f} className="flex items-center gap-2 text-sm text-[#cbd5e1]">
                        <span className="material-symbols-outlined text-sm text-[#16a34a]">check_circle</span>
                        {f}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            );
          })}
        </div>
      </section>
    </div>
  );
}
