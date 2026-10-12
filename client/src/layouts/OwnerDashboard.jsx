import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  BarChart, Bar, AreaChart, Area,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import { HiArrowTrendingUp, HiCalendarDays, HiClock, HiExclamationTriangle } from 'react-icons/hi2';
import api from '../utils/api';

/* ─── helpers ─── */
function fmt(n) {
  return `LKR ${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
function fmtShort(n) {
  const v = Number(n ?? 0);
  if (v >= 1_000_000) return `LKR ${(v / 1_000_000).toFixed(1)}M`;
  if (v >= 1_000)     return `LKR ${(v / 1_000).toFixed(1)}K`;
  return `LKR ${v.toFixed(0)}`;
}
function pct(part, total) {
  return total > 0 ? Math.round((part / total) * 100) : 0;
}

/* ─── constants ─── */
const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAY_LABELS  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const PERIOD_OPTS = [
  { label: '7d',  value: '7d' },
  { label: '30d', value: '30d' },
  { label: '90d', value: '90d' },
  { label: '12m', value: '12m' },
];
const STATUS_PILL = {
  pending:   'bg-[#fff3e0] text-[#e65100]',
  approved:  'bg-ds-primary/10 text-ds-primary',
  completed: 'bg-[#e8f5e9] text-[#2e7d32]',
  rejected:  'bg-ds-error-container text-ds-error',
};
const STATUS_DOT = {
  pending:   'bg-[#f4a261]',
  approved:  'bg-ds-primary',
  completed: 'bg-ds-secondary',
  rejected:  'bg-ds-error',
};
const STATUS_BAR_CFG = [
  { key: 'Pending',   bar: 'bg-[#f4a261]', dot: 'bg-[#f4a261]' },
  { key: 'Approved',  bar: 'bg-ds-primary', dot: 'bg-ds-primary' },
  { key: 'Completed', bar: 'bg-ds-secondary', dot: 'bg-ds-secondary' },
  { key: 'Rejected',  bar: 'bg-ds-error', dot: 'bg-ds-error' },
];
const SERVICE_ICONS = ['content_cut','face_retouching_natural','spa','brush','auto_fix_high'];
const SERVICE_BG    = ['bg-ds-tertiary-fixed','bg-ds-secondary-container','bg-ds-primary-fixed/30','bg-[#e1e3e4]','bg-ds-error-container'];
const SERVICE_CLR   = ['text-ds-tertiary','text-ds-secondary','text-ds-primary','text-ds-on-surface-variant','text-ds-error'];
const DASHBOARD_STAT_CARDS = [
  {
    key: 'revenue-total',
    title: 'Total Revenue',
    titleClass: 'text-ds-on-primary-container',
    valueClass: 'text-white',
    Icon: HiArrowTrendingUp,
    iconWrapClass: 'bg-white/12 text-white',
    cardClass: 'border-0 bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)]',
    orbClass: 'bg-white/6',
    metaClass: 'text-ds-on-primary-container',
    getValue: (stats) => fmtShort(stats?.revenue?.total),
    getMeta: (stats) => `${fmtShort(stats?.revenue?.this_month)} this month`,
  },
  {
    key: 'revenue-today',
    title: 'Today',
    titleClass: 'text-[#ffe2bd]',
    valueClass: 'text-white',
    Icon: HiClock,
    iconWrapClass: 'bg-white/12 text-white',
    cardClass: 'border-0 bg-[linear-gradient(135deg,#7a3e12_0%,#b96516_55%,#e0912d_100%)]',
    orbClass: 'bg-white/8',
    metaClass: 'text-[#ffe2bd]',
    getValue: (stats) => fmtShort(stats?.revenue?.today),
    getMeta: () => 'Live earnings',
  },
  {
    key: 'bookings-total',
    title: 'Total Bookings',
    titleClass: 'text-[#cfeaff]',
    valueClass: 'text-white',
    Icon: HiCalendarDays,
    iconWrapClass: 'bg-white/12 text-white',
    cardClass: 'border-0 bg-[linear-gradient(135deg,#0b3d91_0%,#1e5fbf_55%,#4d8de5_100%)]',
    orbClass: 'bg-white/10',
    metaClass: 'text-[#cfeaff]',
    getValue: (stats) => stats?.bookings?.total ?? 0,
    getMeta: (stats) => `${stats?.bookings?.this_month ?? 0} this month`,
  },
  {
    key: 'bookings-pending',
    title: 'Pending',
    titleClass: 'text-[#ffd5cf]',
    valueClass: 'text-white',
    Icon: HiExclamationTriangle,
    iconWrapClass: 'bg-white/12 text-white',
    cardClass: 'border-0 bg-[linear-gradient(135deg,#7c1820_0%,#a1262d_52%,#c8464c_100%)]',
    orbClass: 'bg-white/8',
    badgeBaseClass: 'text-[10px] font-bold',
    getValue: (stats) => stats?.bookings?.pending ?? 0,
    getBadge: (stats) => {
      const pending = stats?.bookings?.pending ?? 0;
      return {
        label: pending > 0 ? 'Needs action' : 'All clear',
        className: pending > 0 ? 'bg-white/14 text-white' : 'bg-white/16 text-white',
      };
    },
  },
];

/* ─── calendar helpers ─── */
function buildGrid(year, month) {
  const first  = new Date(year, month - 1, 1).getDay();
  const days   = new Date(year, month, 0).getDate();
  const offset = first === 0 ? 6 : first - 1;
  const cells  = Array(offset).fill(null);
  for (let d = 1; d <= days; d++) cells.push(d);
  while (cells.length % 7 !== 0) cells.push(null);
  return cells;
}

/* ─── MonthCalendar ─── */
function MonthCalendar({ bookingsByDate, year, month, selectedDay, onDayClick }) {
  const todayObj = new Date();
  const todayStr = `${todayObj.getFullYear()}-${String(todayObj.getMonth()+1).padStart(2,'0')}-${String(todayObj.getDate()).padStart(2,'0')}`;
  const cells    = useMemo(() => buildGrid(year, month), [year, month]);

  return (
    <div>
      <div className="grid grid-cols-7 mb-1">
        {DAY_LABELS.map(d => (
          <div key={d} className="text-center text-[10px] font-bold text-ds-outline uppercase tracking-widest py-2">{d}</div>
        ))}
      </div>
      <div className="grid grid-cols-7 gap-1">
        {cells.map((day, i) => {
          if (!day) return <div key={`e${i}`} className="min-h-[76px]" />;
          const dateStr    = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
          const dayBkgs    = bookingsByDate?.[dateStr] ?? [];
          const isToday    = dateStr === todayStr;
          const isSelected = selectedDay === dateStr;
          return (
            <button
              key={dateStr}
              onClick={() => onDayClick(isSelected ? null : dateStr)}
              className={`min-h-[76px] rounded-xl p-2 text-left flex flex-col transition-all focus:outline-hidden ${
                isSelected ? 'bg-ds-primary shadow-md ring-2 ring-ds-primary ring-offset-1'
                : isToday  ? 'bg-[#e8f5f5] ring-2 ring-ds-primary/30'
                : dayBkgs.length > 0 ? 'bg-white hover:bg-ds-surface-container-low'
                : 'bg-[#f8f9fa] hover:bg-ds-surface-container-low'
              }`}
            >
              <span className={`text-[11px] font-bold w-6 h-6 flex items-center justify-center rounded-full mb-1 shrink-0 ${
                isSelected ? 'bg-white text-ds-primary'
                : isToday  ? 'bg-ds-primary text-white'
                : 'text-[#191c1d]'
              }`}>{day}</span>
              {dayBkgs.length > 0 && (
                <div className="flex-1 w-full space-y-0.5 overflow-hidden">
                  {dayBkgs.slice(0, 2).map((b, bi) => (
                    <div key={bi} className={`text-[9px] font-semibold px-1.5 py-0.5 rounded truncate leading-tight ${
                      isSelected ? 'bg-white/20 text-white' : STATUS_PILL[b.status] ?? 'bg-[#e1e3e4] text-ds-on-surface-variant'
                    }`}>
                      {b.time?.slice(0, 5)} {b.name}
                    </div>
                  ))}
                  {dayBkgs.length > 2 && (
                    <p className={`text-[9px] font-bold px-1.5 ${isSelected ? 'text-white/70' : 'text-ds-outline'}`}>
                      +{dayBkgs.length - 2} more
                    </p>
                  )}
                </div>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}

/* ─── DayDetail ─── */
function DayDetail({ dateStr, bookings }) {
  if (!dateStr) return null;
  const [yr, mo, dy] = dateStr.split('-').map(Number);
  const label = new Date(yr, mo - 1, dy).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
  return (
    <div className="mt-4 border-t border-[#e1e3e4] pt-4">
      <div className="flex items-center justify-between mb-3">
        <p className="text-xs font-bold text-ds-outline uppercase tracking-widest">{label}</p>
        <span className="text-xs font-bold text-ds-primary bg-ds-primary/10 px-2 py-0.5 rounded-full">
          {bookings.length} booking{bookings.length !== 1 ? 's' : ''}
        </span>
      </div>
      {bookings.length === 0 ? (
        <p className="text-sm text-ds-outline text-center py-4">No bookings this day</p>
      ) : (
        <div className="space-y-2 max-h-52 overflow-y-auto pr-1">
          {bookings.map(b => (
            <div key={b.id} className="flex items-start gap-3 bg-[#f8f9fa] rounded-xl px-3 py-2.5">
              <div className={`w-2 h-2 rounded-full shrink-0 ${STATUS_DOT[b.status] ?? 'bg-ds-outline'}`} />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-bold text-[#191c1d] truncate">{b.name}</p>
                {b.email && (
                  <p className="text-[11px] text-ds-outline truncate mt-1">{b.email}</p>
                )}
                {b.phone && (
                  <p className="text-[11px] text-ds-outline truncate">{b.phone}</p>
                )}
                <p className="text-[11px] text-ds-outline truncate">{b.service_name} · {b.salon_name}</p>
              </div>
              <div className="text-right shrink-0">
                <p className="text-xs font-bold text-[#191c1d]">{b.time?.slice(0, 5)}</p>
                <span className={`text-[9px] font-bold px-1.5 py-0.5 rounded-full capitalize inline-block mt-0.5 ${STATUS_PILL[b.status] ?? ''}`}>
                  {b.status}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* ─── Tooltip formatter ─── */
const chartTooltipStyle = { borderRadius: 8, border: '1px solid #e1e3e4', fontSize: 12, fontFamily: 'Inter' };
function revenueFormatter(v) { return [fmt(v), 'Revenue']; }

/* ─── AnalyticsView ─── */
function AnalyticsView({ salons, salonId }) {
  const today = new Date();
  const [period, setPeriod]           = useState('30d');
  const [profitYear, setProfitYear]   = useState(today.getFullYear());
  const [profitMonth, setProfitMonth] = useState(today.getMonth() + 1);

  const years  = Array.from({ length: 5 }, (_, i) => today.getFullYear() - i);
  const params = (extra = {}) => {
    const p = new URLSearchParams({ period });
    if (salonId) p.set('salon_id', salonId);
    Object.entries(extra).forEach(([k, v]) => p.set(k, v));
    return p.toString();
  };

  const QUERY_OPTS = { staleTime: 60_000, refetchOnWindowFocus: false };

  const { data: analytics, isLoading: aLoading } = useQuery({
    queryKey: ['owner-analytics', period, salonId],
    queryFn: () => api.get(`/owner/dashboard/analytics?${params()}`).then(r => r.data),
    ...QUERY_OPTS,
  });
  const { data: dailyData = [], isLoading: dLoading } = useQuery({
    queryKey: ['owner-daily-revenue', period, salonId],
    queryFn: () => api.get(`/owner/dashboard/daily-revenue?${params()}`).then(r => r.data),
    ...QUERY_OPTS,
  });
  const { data: monthlyData = [], isLoading: mLoading } = useQuery({
    queryKey: ['owner-monthly-revenue', salonId],
    queryFn: () => {
      const p = new URLSearchParams();
      if (salonId) p.set('salon_id', salonId);
      return api.get(`/owner/dashboard/monthly-revenue?${p}`).then(r => r.data);
    },
    ...QUERY_OPTS,
  });
  const { data: profit } = useQuery({
    queryKey: ['owner-profit-breakdown', salonId],
    queryFn: () => {
      const p = new URLSearchParams();
      if (salonId) p.set('salon_id', salonId);
      return api.get(`/owner/dashboard/profit-breakdown?${p}`).then(r => r.data);
    },
    ...QUERY_OPTS,
  });
  const { data: profitMonth_data, isLoading: pmLoading } = useQuery({
    queryKey: ['owner-profit-by-month', profitYear, profitMonth, salonId],
    queryFn: () => {
      const p = new URLSearchParams({ year: profitYear, month: profitMonth });
      if (salonId) p.set('salon_id', salonId);
      return api.get(`/owner/dashboard/profit-by-month?${p}`).then(r => r.data);
    },
    ...QUERY_OPTS,
  });
  const { data: topServices = [] } = useQuery({
    queryKey: ['owner-top-services', period, salonId],
    queryFn: () => {
      const p = new URLSearchParams({ period });
      if (salonId) p.set('salon_id', salonId);
      return api.get(`/owner/dashboard/top-services?${p}`).then(r => r.data);
    },
    ...QUERY_OPTS,
  });

  const bkgs   = analytics?.bookings ?? {};
  const bTotal = (bkgs.total ?? 0);

  const maxRev = Math.max(...topServices.map(s => s.revenue ?? 0), 1);

  return (
    <div className="space-y-6">

      {/* ── Row 0: period filter ── */}
      <div className="inline-flex flex-wrap items-center gap-2 rounded-[1.25rem] border border-[#d6e7e7] bg-[linear-gradient(135deg,#f8fbfb_0%,#eef6f6_100%)] p-1.5 shadow-[0_16px_40px_rgba(0,66,66,0.08)]">
        {PERIOD_OPTS.map(o => (
          <button key={o.value} onClick={() => setPeriod(o.value)}
            className={`rounded-full px-4 py-2 text-sm font-bold tracking-wide transition-all ${
              period === o.value
                ? 'bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_12px_28px_rgba(0,66,66,0.22)]'
                : 'bg-white/70 text-[#5f6f6f] hover:bg-white hover:text-ds-primary'
            }`}>{o.label}</button>
        ))}
      </div>

      {/* ── Row 1: 4 stat cards ── */}
      {aLoading ? (
        <div className="h-28 flex items-center justify-center"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" /></div>
      ) : (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Period revenue */}
          <div className="bg-white p-5 rounded-lg relative overflow-hidden">
            <div className="absolute top-0 left-0 w-1 h-full bg-ds-primary" />
            <p className="text-[10px] font-bold text-ds-outline uppercase tracking-widest mb-1">
              {period === '12m' ? '12-Month' : period.toUpperCase()} Revenue
            </p>
            <h3 className="font-headline text-2xl font-extrabold text-ds-primary">{fmtShort(analytics?.revenue?.period)}</h3>
            <p className="mt-2 text-[11px] text-ds-outline font-medium">All-time: {fmtShort(analytics?.revenue?.total)}</p>
          </div>
          {/* Today */}
          <div className="bg-white p-5 rounded-lg">
            <p className="text-[10px] font-bold text-ds-outline uppercase tracking-widest mb-1">Today</p>
            <h3 className="font-headline text-2xl font-extrabold text-[#191c1d]">{fmtShort(analytics?.revenue?.today)}</h3>
            <p className="mt-2 text-[11px] text-ds-outline font-medium flex items-center gap-1">
              <span className="material-symbols-outlined text-sm">today</span>Live earnings
            </p>
          </div>
          {/* Period bookings */}
          <div className="bg-white p-5 rounded-lg">
            <p className="text-[10px] font-bold text-ds-outline uppercase tracking-widest mb-1">Period Bookings</p>
            <h3 className="font-headline text-2xl font-extrabold text-[#191c1d]">{bkgs.total ?? 0}</h3>
            <p className="mt-2 text-[11px] text-ds-secondary font-medium">
              {bkgs.current_month ?? 0}
              {bkgs.max_per_month ? ` / ${bkgs.max_per_month}` : ''} this month
            </p>
          </div>
          {/* Avg rating */}
          <div className="bg-white p-5 rounded-lg">
            <p className="text-[10px] font-bold text-ds-outline uppercase tracking-widest mb-1">Avg Rating</p>
            <h3 className="font-headline text-2xl font-extrabold text-[#191c1d] flex items-center gap-1">
              {analytics?.avg_rating?.toFixed(1) ?? '—'}
              <span className="material-symbols-outlined text-xl text-[#f4a261]">star</span>
            </h3>
            <p className="mt-2 text-[11px] text-ds-outline font-medium">Across all salons</p>
          </div>
        </div>
      )}

      {/* ── Row 2: booking breakdown + monthly usage ── */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {/* Status bars */}
        <div className="xl:col-span-2 bg-white p-6 rounded-lg">
          <h2 className="font-headline text-base font-bold mb-5">Booking Breakdown <span className="text-ds-outline font-normal text-sm">· {period}</span></h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {STATUS_BAR_CFG.map(cfg => {
              const key   = cfg.key.toLowerCase();
              const count = (key === 'approved' ? (bkgs.approved ?? 0) + (bkgs.completed ?? 0) : bkgs[key] ?? 0);
              const p     = pct(count, bTotal);
              return (
                <div key={cfg.key}>
                  <div className="flex items-center justify-between mb-1.5">
                    <div className="flex items-center gap-2">
                      <div className={`w-2.5 h-2.5 rounded-full ${cfg.dot}`} />
                      <span className="text-sm font-semibold text-[#191c1d]">{cfg.key}</span>
                    </div>
                    <span className="font-bold text-sm text-[#191c1d]">{count} <span className="text-ds-outline font-normal text-xs">({p}%)</span></span>
                  </div>
                  <div className="w-full bg-ds-surface-container-low h-2 rounded-full overflow-hidden">
                    <div className={`${cfg.bar} h-full rounded-full transition-all duration-700`} style={{ width: `${p}%` }} />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Monthly usage */}
        <div className="bg-ds-primary rounded-lg p-6 text-white relative overflow-hidden">
          <div className="absolute bottom-0 right-0 w-36 h-36 rounded-full bg-white/5 translate-x-8 translate-y-8" />
          <div className="absolute top-2 right-2 w-16 h-16 rounded-full bg-white/5" />
          <p className="text-[10px] font-bold uppercase tracking-widest text-ds-on-primary-container mb-1 relative z-10">This Month</p>
          <p className="font-headline text-4xl font-extrabold text-white relative z-10">
            {bkgs.current_month ?? 0}
            {bkgs.max_per_month && <span className="text-white/50 text-xl"> / {bkgs.max_per_month}</span>}
          </p>
          {bkgs.max_per_month && (
            <>
              <div className="mt-4 w-full bg-white/20 h-2 rounded-full overflow-hidden relative z-10">
                <div
                  className={`h-full rounded-full transition-all duration-700 ${
                    pct(bkgs.current_month, bkgs.max_per_month) >= 90 ? 'bg-ds-error' : 'bg-ds-on-primary-container'
                  }`}
                  style={{ width: `${Math.min(100, pct(bkgs.current_month, bkgs.max_per_month))}%` }}
                />
              </div>
              <p className="text-xs text-white/70 mt-2 relative z-10">
                {Math.max(0, bkgs.max_per_month - (bkgs.current_month ?? 0))} bookings remaining
              </p>
            </>
          )}
          <div className="mt-5 flex items-center gap-2 text-ds-on-primary-container text-xs font-bold relative z-10">
            <span className="material-symbols-outlined text-sm">check_circle</span>
            {(bkgs.approved ?? 0) + (bkgs.completed ?? 0)} approved this period
          </div>
        </div>
      </div>

      {/* ── Row 3: charts ── */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {/* Monthly rolling 12m bar */}
        <div className="bg-white p-6 rounded-lg">
          <h2 className="font-headline text-base font-bold mb-1">Monthly Revenue</h2>
          <p className="text-sm text-ds-outline mb-5">Rolling last 12 months</p>
          {mLoading ? (
            <div className="h-52 flex items-center justify-center"><div className="animate-spin rounded-full h-6 w-6 border-b-2 border-ds-primary" /></div>
          ) : (
            <ResponsiveContainer width="100%" height={210}>
              <BarChart data={monthlyData} margin={{ top: 4, right: 4, left: -8, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f3f4f5" />
                <XAxis dataKey="month" tick={{ fontSize: 10, fill: '#6f7979', fontWeight: 700 }} axisLine={false} tickLine={false}
                  tickFormatter={v => v.split(' ')[0]} />
                <YAxis tick={{ fontSize: 10, fill: '#6f7979' }} axisLine={false} tickLine={false} width={38}
                  tickFormatter={v => v >= 1000 ? `${(v/1000).toFixed(0)}K` : v} />
                <Tooltip cursor={{ fill: 'rgba(0,66,66,0.04)' }} formatter={revenueFormatter} contentStyle={chartTooltipStyle} />
                <Bar dataKey="revenue" fill="#004242" radius={[4, 4, 0, 0]} maxBarSize={28} />
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        {/* Daily/period area chart */}
        <div className="bg-white p-6 rounded-lg">
          <h2 className="font-headline text-base font-bold mb-1">
            {period === '12m' ? 'Monthly' : 'Daily'} Revenue Trend
          </h2>
          <p className="text-sm text-ds-outline mb-5">Last {period}</p>
          {dLoading ? (
            <div className="h-52 flex items-center justify-center"><div className="animate-spin rounded-full h-6 w-6 border-b-2 border-ds-primary" /></div>
          ) : (
            <ResponsiveContainer width="100%" height={210}>
              <AreaChart data={dailyData} margin={{ top: 4, right: 4, left: -8, bottom: 0 }}>
                <defs>
                  <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#004242" stopOpacity={0.15} />
                    <stop offset="95%" stopColor="#004242" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f3f4f5" />
                <XAxis dataKey="date" tick={{ fontSize: 10, fill: '#6f7979', fontWeight: 600 }} axisLine={false} tickLine={false}
                  interval={period === '7d' ? 0 : period === '30d' ? 4 : period === '90d' ? 9 : 1} />
                <YAxis tick={{ fontSize: 10, fill: '#6f7979' }} axisLine={false} tickLine={false} width={38}
                  tickFormatter={v => v >= 1000 ? `${(v/1000).toFixed(0)}K` : v} />
                <Tooltip cursor={{ stroke: '#004242', strokeWidth: 1, strokeDasharray: '3 3' }}
                  formatter={revenueFormatter} contentStyle={chartTooltipStyle} />
                <Area dataKey="revenue" stroke="#004242" strokeWidth={2} fill="url(#areaGrad)" dot={false} />
              </AreaChart>
            </ResponsiveContainer>
          )}
        </div>
      </div>

      {/* ── Row 4: top services + profit breakdown ── */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {/* Top services */}
        <div className="bg-white rounded-lg overflow-hidden">
          <div className="px-6 py-5 border-b border-[#e1e3e4]/60">
            <h2 className="font-headline text-base font-bold">Top Services</h2>
            <p className="text-sm text-ds-outline">By bookings · {period}</p>
          </div>
          {topServices.length === 0 ? (
            <div className="text-center py-12 text-ds-outline">
              <span className="material-symbols-outlined text-3xl block mb-2">content_cut</span>
              No data for this period
            </div>
          ) : (
            <div>
              {topServices.map((svc, i) => (
                <div key={i} className="px-6 py-4 flex items-center gap-4 hover:bg-[#f8f9fa] transition-colors">
                  <span className="text-xs font-black text-ds-outline-variant w-4 text-center">{i + 1}</span>
                  <div className={`w-10 h-10 rounded-xl ${SERVICE_BG[i % SERVICE_BG.length]} flex items-center justify-center shrink-0`}>
                    <span className={`material-symbols-outlined text-lg ${SERVICE_CLR[i % SERVICE_CLR.length]}`}>
                      {SERVICE_ICONS[i % SERVICE_ICONS.length]}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-bold text-[#191c1d] truncate">{svc.name}</p>
                    <div className="mt-1 w-full bg-ds-surface-container-low h-1.5 rounded-full overflow-hidden">
                      <div className="bg-ds-primary h-full rounded-full transition-all duration-700"
                        style={{ width: `${pct(svc.revenue, maxRev)}%` }} />
                    </div>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="text-sm font-bold text-ds-primary">{fmtShort(svc.revenue)}</p>
                    <p className="text-[10px] text-ds-outline">{svc.booking_count ?? svc.bookings} bkgs</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Business snapshot */}
        <div className="bg-white rounded-lg p-6">
          <h2 className="font-headline text-base font-bold mb-1">Business Snapshot</h2>
          <p className="text-sm text-ds-outline mb-6">All-time performance from approved and completed bookings</p>

          <div className="space-y-4">
            {/* Total revenue */}
            <div className="flex items-center justify-between py-3 border-b border-ds-surface-container-low">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-ds-primary/8 flex items-center justify-center">
                  <span className="material-symbols-outlined text-base text-ds-primary">payments</span>
                </div>
                <div>
                  <p className="text-sm font-bold text-[#191c1d]">Total Revenue</p>
                  <p className="text-[10px] text-ds-outline">Approved + completed bookings</p>
                </div>
              </div>
              <p className="font-headline text-lg font-extrabold text-ds-primary">{fmtShort(profit?.total_revenue)}</p>
            </div>

            {/* Confirmed bookings */}
            <div className="flex items-center justify-between py-3 border-b border-ds-surface-container-low">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-[#e8f5e9] flex items-center justify-center">
                  <span className="material-symbols-outlined text-base text-[#2e7d32]">event_available</span>
                </div>
                <div>
                  <p className="text-sm font-bold text-[#191c1d]">Confirmed Bookings</p>
                  <p className="text-[10px] text-ds-outline">Approved and completed appointments</p>
                </div>
              </div>
              <p className="font-headline text-lg font-extrabold text-[#2e7d32]">{profit?.confirmed_bookings ?? 0}</p>
            </div>

            {/* Average booking value */}
            <div className="flex items-center justify-between py-3 border-b border-ds-surface-container-low">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-[#fff3e0] flex items-center justify-center">
                  <span className="material-symbols-outlined text-base text-[#e65100]">sell</span>
                </div>
                <div>
                  <p className="text-sm font-bold text-[#191c1d]">Average Booking Value</p>
                  <p className="text-[10px] text-ds-outline">Revenue per confirmed booking</p>
                </div>
              </div>
              <p className="font-headline text-lg font-extrabold text-[#e65100]">{fmtShort(profit?.average_booking_value)}</p>
            </div>
          </div>

          <div className="mt-5 pt-4 border-t border-ds-surface-container-low">
            <div className="flex items-center justify-between py-3">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-ds-secondary-container flex items-center justify-center">
                  <span className="material-symbols-outlined text-base text-ds-primary">workspace_premium</span>
                </div>
                <div>
                  <p className="text-sm font-bold text-[#191c1d]">Top Service</p>
                  <p className="text-[10px] text-ds-outline">
                    {profit?.top_service
                      ? `${profit.top_service.bookings ?? 0} bookings`
                      : 'No confirmed bookings yet'}
                  </p>
                </div>
              </div>
              <div className="text-right">
                <p className="font-headline text-base font-extrabold text-ds-primary">
                  {profit?.top_service?.name ?? 'N/A'}
                </p>
                <p className="text-[10px] text-ds-outline">
                  {profit?.top_service ? fmtShort(profit.top_service.revenue) : 'No revenue yet'}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Row 5: profit by month ── */}
      <div className="overflow-hidden rounded-2xl border border-[#d6e7e7] bg-[linear-gradient(180deg,#ffffff_0%,#fbfdfd_100%)] shadow-[0_22px_48px_rgba(25,28,29,0.08)]">
        <div className="relative overflow-hidden px-6 py-5 border-b border-[#e1e3e4]/60 flex flex-wrap items-center justify-between gap-3 bg-[linear-gradient(135deg,#f7fbfb_0%,#edf6f6_100%)]">
          <div className="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-ds-on-primary-container/18" />
          <div className="absolute right-14 bottom-[-22px] h-16 w-16 rounded-full bg-ds-primary/6" />
          <div>
            <div className="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-ds-secondary shadow-[0_10px_24px_rgba(0,66,66,0.08)]">
              <span className="material-symbols-outlined text-sm">insights</span>
              Monthly Profit
            </div>
            <h2 className="mt-3 font-headline text-lg font-bold text-[#191c1d]">Profit by Month</h2>
            <p className="text-sm text-ds-outline">Revenue breakdown per salon</p>
          </div>
          <div className="relative z-10 flex items-center gap-2 rounded-full border border-white/70 bg-white/75 p-1 shadow-[0_12px_28px_rgba(0,66,66,0.08)] backdrop-blur-sm">
            <select value={profitYear} onChange={e => setProfitYear(Number(e.target.value))}
              className="text-sm border border-[#d9e6e6] bg-white rounded-full px-4 py-2 font-semibold text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20">
              {years.map(y => <option key={y} value={y}>{y}</option>)}
            </select>
            <select value={profitMonth} onChange={e => setProfitMonth(Number(e.target.value))}
              className="text-sm border border-[#d9e6e6] bg-white rounded-full px-4 py-2 font-semibold text-ds-on-surface-variant focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20">
              {MONTH_NAMES.map((m, i) => <option key={i+1} value={i+1}>{m}</option>)}
            </select>
          </div>
        </div>

        {pmLoading ? (
          <div className="h-32 flex items-center justify-center"><div className="animate-spin rounded-full h-6 w-6 border-b-2 border-ds-primary" /></div>
        ) : !profitMonth_data?.by_salon?.length ? (
          <div className="px-6 py-10">
            <div className="mx-auto flex max-w-xl flex-col items-center rounded-[1.25rem] border border-dashed border-[#c8d8d8] bg-[linear-gradient(180deg,#fbfdfd_0%,#f3f8f8_100%)] px-6 py-10 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]">
              <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_18px_32px_rgba(0,66,66,0.22)]">
                <span className="material-symbols-outlined text-3xl">bar_chart</span>
              </div>
              <p className="mt-5 text-sm font-bold uppercase tracking-[0.24em] text-ds-outline">
                {MONTH_NAMES[profitMonth - 1]} {profitYear}
              </p>
              <h3 className="mt-2 font-headline text-xl font-bold text-[#191c1d]">No profit data yet</h3>
              <p className="mt-2 max-w-md text-sm leading-relaxed text-ds-outline">
                There&apos;s no revenue breakdown per salon for {MONTH_NAMES[profitMonth - 1]} {profitYear} yet. Once bookings are approved or completed, this card will update automatically.
              </p>
            </div>
          </div>
        ) : (
          <div>
            {/* Summary row */}
            <div className="px-6 py-4 bg-ds-primary/4 flex items-center justify-between">
              <p className="text-sm font-bold text-[#191c1d]">
                {MONTH_NAMES[profitMonth - 1]} {profitYear} — Total
              </p>
              <p className="font-headline text-lg font-extrabold text-ds-primary">{fmt(profitMonth_data.total_revenue)}</p>
            </div>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-ds-surface-container-low">
                  {['Salon', 'Bookings', 'Revenue', 'Share'].map(h => (
                    <th key={h} className="text-left px-6 py-3 text-[10px] font-bold text-ds-outline uppercase tracking-widest">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {profitMonth_data.by_salon.map((row, i) => {
                  const share = pct(row.revenue, profitMonth_data.total_revenue);
                  return (
                    <tr key={i} className="border-b border-[#f8f9fa] hover:bg-[#f8f9fa] transition-colors">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <div className="w-7 h-7 rounded-full bg-ds-primary/10 flex items-center justify-center shrink-0">
                            <span className="material-symbols-outlined text-sm text-ds-primary">store</span>
                          </div>
                          <span className="font-semibold text-[#191c1d]">{row.name}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 font-medium text-ds-on-surface-variant">{row.bookings}</td>
                      <td className="px-6 py-4 font-bold text-ds-primary">{fmt(row.revenue)}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-ds-surface-container-low h-1.5 rounded-full overflow-hidden max-w-[80px]">
                            <div className="bg-ds-primary h-full rounded-full" style={{ width: `${share}%` }} />
                          </div>
                          <span className="text-xs font-bold text-ds-outline">{share}%</span>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

/* ─── OwnerDashboard (main) ─── */
export default function OwnerDashboard() {
  const today = new Date();

  const [mainTab, setMainTab]           = useState('calendar'); // 'calendar' | 'analytics'
  const [calMonth, setCalMonth]         = useState(today.getMonth() + 1);
  const [calYear, setCalYear]           = useState(today.getFullYear());
  const [selectedDay, setSelectedDay]   = useState(null);
  const [calSalonId, setCalSalonId]     = useState('');

  /* queries always needed */
  const MAIN_QUERY_OPTS = { staleTime: 60_000, refetchOnWindowFocus: false };

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['owner-stats'],
    queryFn: () => api.get('/owner/dashboard/stats').then(r => r.data),
    ...MAIN_QUERY_OPTS,
  });
  const { data: salons = [] } = useQuery({
    queryKey: ['owner-salons'],
    queryFn: () => api.get('/owner/dashboard/salons').then(r => r.data),
    staleTime: 5 * 60_000, // salons change rarely
    refetchOnWindowFocus: false,
  });
  const { data: bookingStatus = [] } = useQuery({
    queryKey: ['owner-bookings-by-status'],
    queryFn: () => api.get('/owner/dashboard/bookings-by-status').then(r => r.data),
    enabled: mainTab === 'calendar',
    ...MAIN_QUERY_OPTS,
  });
  const { data: calData, isLoading: calLoading } = useQuery({
    queryKey: ['owner-calendar', calYear, calMonth, calSalonId],
    queryFn: () => {
      const p = new URLSearchParams({ year: calYear, month: calMonth });
      if (calSalonId) p.set('salon_id', calSalonId);
      return api.get(`/owner/dashboard/calendar?${p}`).then(r => r.data);
    },
    enabled: mainTab === 'calendar',
    ...MAIN_QUERY_OPTS,
  });

  if (statsLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-ds-primary" />
      </div>
    );
  }

  const sub         = stats?.subscription;
  const hasAnalytics = sub?.features?.analytics === true;
  const hasCalendar  = sub?.features?.calendar  === true;

  const bookingTotal =
    (stats?.bookings?.pending   ?? 0) +
    (stats?.bookings?.approved  ?? 0) +
    (stats?.bookings?.completed ?? 0) +
    (stats?.bookings?.rejected  ?? 0);

  const bookingsByDate  = calData?.bookings ?? {};
  const calMonthTotal   = Object.values(bookingsByDate).reduce((s, a) => s + a.length, 0);
  const selectedDayBkgs = selectedDay ? (bookingsByDate[selectedDay] ?? []) : [];

  const prevMonth = () => {
    setSelectedDay(null);
    if (calMonth === 1) { setCalYear(y => y - 1); setCalMonth(12); } else setCalMonth(m => m - 1);
  };
  const nextMonth = () => {
    setSelectedDay(null);
    if (calMonth === 12) { setCalYear(y => y + 1); setCalMonth(1); } else setCalMonth(m => m + 1);
  };

  return (
    <div className="space-y-6">

      {/* ── Subscription banner ── */}
      {sub ? (
        <div className={`rounded-lg p-5 flex flex-wrap items-center justify-between gap-4 ${
          sub.days_remaining <= 7 ? 'bg-ds-error-container' : 'bg-ds-primary/5 border border-ds-primary/10'
        }`}>
          <div className="flex items-center gap-4">
            <div className="w-10 h-10 rounded-full bg-ds-primary-container flex items-center justify-center shrink-0">
              <span className="material-symbols-outlined text-ds-on-primary-container text-xl">card_membership</span>
            </div>
            <div>
              <p className="text-[10px] font-bold text-ds-outline uppercase tracking-widest">Current Plan</p>
              <p className="font-headline text-lg font-extrabold text-ds-primary">{sub.package_name ?? 'Unknown'}</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide ${
              sub.status === 'active'  ? 'bg-ds-primary/10 text-ds-primary' :
              sub.status === 'pending' ? 'bg-ds-tertiary/10 text-ds-tertiary' :
              'bg-ds-error/10 text-ds-error'
            }`}>{sub.status}</span>
            {sub.is_trial && (
              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#f4a261]/20 text-[#8a4b15] border border-[#f4a261]/30">
                <span className="material-symbols-outlined text-[13px] leading-none">redeem</span>
                Free Trial
              </span>
            )}
            {sub.status === 'active' && (
              <span className={`text-sm font-semibold ${sub.days_remaining <= 7 ? 'text-ds-error' : 'text-ds-outline'}`}>
                {sub.days_remaining} day{sub.days_remaining !== 1 ? 's' : ''} left
              </span>
            )}
            {sub.features && (
              <div className="flex flex-wrap gap-2">
                {[
                  sub.features.max_salons != null && {
                    label: `${sub.features.max_salons} salons`,
                    icon: 'storefront',
                    className: 'border-[#0f5b5c] bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white',
                  },
                  sub.features.max_staff != null && {
                    label: `${sub.features.max_staff} staff`,
                    icon: 'group',
                    className: 'border-[#156869] bg-[linear-gradient(135deg,#0b4b4c_0%,#1b7475_100%)] text-white',
                  },
                  sub.features.max_services != null && {
                    label: `${sub.features.max_services} services`,
                    icon: 'content_cut',
                    className: 'border-[#115a5b] bg-[linear-gradient(135deg,#0a4344_0%,#146566_100%)] text-white',
                  },
                  sub.features.calendar && {
                    label: 'Calendar',
                    icon: 'calendar_month',
                    className: 'border-[#156869] bg-[linear-gradient(135deg,#0d4b4c_0%,#197273_100%)] text-white',
                  },
                  sub.features.analytics && {
                    label: 'Analytics',
                    icon: 'bar_chart',
                    className: 'border-[#0f5f60] bg-[linear-gradient(135deg,#004242_0%,#0f6a6b_100%)] text-white',
                  },
                  sub.features.priority_support && {
                    label: 'Priority support',
                    icon: 'workspace_premium',
                    className: 'border-[#145e5f] bg-[linear-gradient(135deg,#0b4546_0%,#156b6c_100%)] text-white',
                  },
                ].filter(Boolean).map((feat) => (
                  <span
                    key={feat.label}
                    className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold shadow-[0_8px_24px_rgba(25,28,29,0.06)] ${feat.className}`}
                  >
                    <span className="material-symbols-outlined text-[15px] leading-none">{feat.icon}</span>
                    {feat.label}
                  </span>
                ))}
              </div>
            )}
          </div>
        </div>
      ) : (
        <div className="rounded-lg p-5 bg-ds-error-container flex items-center gap-3">
          <span className="material-symbols-outlined text-ds-error">warning</span>
          <p className="text-sm text-ds-error font-semibold">No active subscription. Please subscribe to unlock all features.</p>
        </div>
      )}

      {/* ── Stat cards ── */}
      <section className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {DASHBOARD_STAT_CARDS.map((card) => {
          const badge = card.getBadge?.(stats);
          const Icon = card.Icon;

          return (
            <div
              key={card.key}
              className={`relative overflow-hidden rounded-[1.1rem] p-5 shadow-[0_18px_45px_rgba(25,28,29,0.10)] ${card.cardClass}`}
            >
              <div className={`absolute -right-6 -bottom-6 h-24 w-24 rounded-full ${card.orbClass}`} />
              <div className={`absolute right-4 top-4 h-16 w-16 rounded-full ${card.orbClass}`} />
              <div className="relative flex h-full flex-col justify-between gap-5">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className={`text-[10px] font-bold uppercase tracking-[0.22em] ${card.titleClass}`}>{card.title}</p>
                    <h3 className={`mt-3 font-headline text-2xl font-extrabold ${card.valueClass}`}>
                      {card.getValue(stats)}
                    </h3>
                  </div>
                  <div className={`flex h-12 w-12 items-center justify-center rounded-2xl ${card.iconWrapClass}`}>
                    <Icon className="text-[24px]" />
                  </div>
                </div>

                {badge ? (
                  <div className="mt-auto">
                    <span className={`inline-flex items-center rounded-full px-3 py-1 ${card.badgeBaseClass ?? 'text-[11px] font-semibold'} ${badge.className}`}>
                      {badge.label}
                    </span>
                  </div>
                ) : (
                  <p className={`mt-auto flex items-center gap-2 text-[11px] font-semibold ${card.metaClass}`}>
                    <Icon className="text-[16px]" />
                    {card.getMeta(stats)}
                  </p>
                )}
              </div>
            </div>
          );
        })}
      </section>

      {/* ── Main tab switcher ── */}
      <div className="inline-flex flex-wrap items-center gap-2 rounded-[1.3rem] border border-[#d6e7e7] bg-[linear-gradient(135deg,#f8fbfb_0%,#eef6f6_100%)] p-1.5 shadow-[0_16px_40px_rgba(0,66,66,0.08)]">
        {hasCalendar ? (
          <button onClick={() => setMainTab('calendar')}
            className={`flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition-all ${
              mainTab === 'calendar'
                ? 'bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_12px_28px_rgba(0,66,66,0.22)]'
                : 'bg-white/70 text-[#5f6f6f] hover:bg-white hover:text-ds-primary'
            }`}>
            <span className={`material-symbols-outlined text-[18px] ${mainTab === 'calendar' ? 'text-white' : 'text-ds-secondary'}`}>calendar_month</span>
            Calendar
          </button>
        ) : (
          <div title="Calendar requires an upgraded plan"
            className="flex items-center gap-2 rounded-full border border-white/60 bg-white/45 px-5 py-2.5 text-sm font-semibold text-[#a6b5b5] cursor-not-allowed select-none">
            <span className="material-symbols-outlined text-[18px]">lock</span>
            Calendar
          </div>
        )}
        {hasAnalytics ? (
          <button onClick={() => setMainTab('analytics')}
            className={`flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition-all ${
              mainTab === 'analytics'
                ? 'bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] text-white shadow-[0_12px_28px_rgba(0,66,66,0.22)]'
                : 'bg-white/70 text-[#5f6f6f] hover:bg-white hover:text-ds-primary'
            }`}>
            <span className={`material-symbols-outlined text-[18px] ${mainTab === 'analytics' ? 'text-white' : 'text-ds-secondary'}`}>bar_chart</span>
            Analytics
          </button>
        ) : (
          <div title="Analytics requires an upgraded plan"
            className="flex items-center gap-2 rounded-full border border-white/60 bg-white/45 px-5 py-2.5 text-sm font-semibold text-[#a6b5b5] cursor-not-allowed select-none">
            <span className="material-symbols-outlined text-[18px]">lock</span>
            Analytics
          </div>
        )}
      </div>

      {/* ── Calendar locked gate ── */}
      {mainTab === 'calendar' && !hasCalendar && (
        <div className="bg-white rounded-lg p-16 text-center">
          <div className="w-16 h-16 rounded-full bg-[#e1e3e4] flex items-center justify-center mx-auto mb-5">
            <span className="material-symbols-outlined text-3xl text-ds-outline">lock</span>
          </div>
          <h3 className="font-headline text-xl font-bold text-[#191c1d] mb-2">Calendar Locked</h3>
          <p className="text-sm text-ds-outline max-w-sm mx-auto mb-6">
            Upgrade your subscription plan to unlock the booking calendar view.
          </p>
          <a href="/owner/subscription"
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-ds-primary text-white text-sm font-bold hover:bg-ds-primary-container transition-colors">
            <span className="material-symbols-outlined text-base">upgrade</span>
            Upgrade Plan
          </a>
        </div>
      )}

      {/* ── Calendar view ── */}
      {mainTab === 'calendar' && hasCalendar && (
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">

          {/* Calendar */}
          <div className="xl:col-span-2 bg-white rounded-lg p-6">
            <div className="flex flex-wrap items-start justify-between gap-3 mb-5">
              <div>
                <h2 className="font-headline text-lg font-bold text-[#191c1d]">Booking Calendar</h2>
                <p className="text-sm text-ds-outline">{calMonthTotal} booking{calMonthTotal !== 1 ? 's' : ''} · {MONTH_NAMES[calMonth - 1]} {calYear}</p>
              </div>
              <div className="flex items-center gap-2 flex-wrap">
                {salons.length > 1 && (
                  <select value={calSalonId} onChange={e => { setCalSalonId(e.target.value); setSelectedDay(null); }}
                    className="text-xs border border-[#e1e3e4] bg-white rounded-full px-3 py-1.5 font-semibold text-ds-outline focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20">
                    <option value="">All salons</option>
                    {salons.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                  </select>
                )}
                <div className="flex items-center gap-0.5 bg-ds-surface-container-low rounded-full px-1 py-1">
                  <button onClick={prevMonth} className="p-1 rounded-full hover:bg-white transition-colors text-ds-outline">
                    <span className="material-symbols-outlined text-[18px]">chevron_left</span>
                  </button>
                  <span className="font-headline text-xs font-bold text-[#191c1d] px-3 min-w-[124px] text-center">
                    {MONTH_NAMES[calMonth - 1]} {calYear}
                  </span>
                  <button onClick={nextMonth} className="p-1 rounded-full hover:bg-white transition-colors text-ds-outline">
                    <span className="material-symbols-outlined text-[18px]">chevron_right</span>
                  </button>
                </div>
              </div>
            </div>
            {/* Legend */}
            <div className="flex flex-wrap items-center gap-4 mb-4">
              {[{ label:'Pending',dot:'bg-[#f4a261]'},{label:'Approved',dot:'bg-ds-primary'},{label:'Completed',dot:'bg-ds-secondary'},{label:'Rejected',dot:'bg-ds-error'}].map(l => (
                <div key={l.label} className="flex items-center gap-1.5">
                  <div className={`w-2 h-2 rounded-full ${l.dot}`} />
                  <span className="text-[10px] font-semibold text-ds-outline">{l.label}</span>
                </div>
              ))}
            </div>
            {calLoading ? (
              <div className="h-80 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
              </div>
            ) : (
              <>
                <MonthCalendar bookingsByDate={bookingsByDate} year={calYear} month={calMonth} selectedDay={selectedDay} onDayClick={setSelectedDay} />
                <DayDetail dateStr={selectedDay} bookings={selectedDayBkgs} />
              </>
            )}
          </div>

          {/* Overview sidebar */}
          <div className="space-y-5">
            {/* Booking status */}
            <div className="bg-ds-surface-container-low p-6 rounded-lg">
              <h2 className="font-headline text-base font-bold mb-5">Booking Status</h2>
              <div className="space-y-4">
                {STATUS_BAR_CFG.map(cfg => {
                  const item  = bookingStatus.find(d => d.status?.toLowerCase() === cfg.key.toLowerCase());
                  const count = item?.count ?? 0;
                  const p     = pct(count, bookingTotal);
                  return (
                    <div key={cfg.key}>
                      <div className="flex items-center justify-between mb-1.5">
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${cfg.dot}`} />
                          <span className="text-sm font-medium text-[#191c1d]">{cfg.key}</span>
                        </div>
                        <span className="font-bold text-sm text-[#191c1d]">{count}</span>
                      </div>
                      <div className="w-full bg-[#e1e3e4] h-1.5 rounded-full overflow-hidden">
                        <div className={`${cfg.bar} h-full rounded-full transition-all duration-700`} style={{ width: `${p}%` }} />
                      </div>
                    </div>
                  );
                })}
              </div>
              <div className="mt-5 pt-4 border-t border-ds-outline-variant/40 grid grid-cols-2 gap-3 text-center">
                <div>
                  <p className="font-headline text-xl font-extrabold text-ds-primary">{stats?.salons?.count ?? 0}</p>
                  <p className="text-[10px] text-ds-outline uppercase tracking-widest font-semibold mt-0.5">Salons</p>
                </div>
                <div>
                  <p className="font-headline text-xl font-extrabold text-[#191c1d]">{stats?.bookings?.completed ?? 0}</p>
                  <p className="text-[10px] text-ds-outline uppercase tracking-widest font-semibold mt-0.5">Completed</p>
                </div>
              </div>
            </div>

            {/* Salon capacity */}
            <div className="relative bg-ds-primary rounded-lg overflow-hidden">
              <div className="absolute inset-0 bg-linear-to-br from-ds-primary via-ds-primary-container to-ds-surface-tint" />
              <div className="absolute bottom-0 right-0 w-40 h-40 rounded-full bg-white/5 translate-x-8 translate-y-8" />
              <div className="absolute top-4 right-4 w-16 h-16 rounded-full bg-white/5" />
              <div className="relative z-10 p-6">
                <p className="text-[10px] font-bold uppercase tracking-widest text-ds-on-primary-container mb-1">Salon Capacity</p>
                <p className="font-headline text-3xl font-extrabold text-white">
                  {stats?.salons?.count ?? 0}
                  {stats?.salons?.max_allowed && <span className="text-white/50 text-lg"> / {stats.salons.max_allowed}</span>}
                </p>
                <p className="text-sm text-white/70 leading-relaxed mt-3">
                  {stats?.salons?.max_allowed
                    ? `${Math.max(0, stats.salons.max_allowed - (stats.salons.count ?? 0))} slot${stats.salons.max_allowed - (stats.salons.count ?? 0) !== 1 ? 's' : ''} available.`
                    : 'Manage from your subscription plan.'}
                </p>
                <div className="mt-4 flex items-center gap-2 text-ds-on-primary-container text-xs font-bold">
                  <span className="material-symbols-outlined text-sm">storefront</span>
                  {stats?.bookings?.approved ?? 0} approved bookings
                </div>
              </div>
            </div>

            {/* Plan limits */}
            {sub?.features && (
              <div className="bg-white rounded-lg p-5">
                <h3 className="font-headline text-sm font-bold text-[#191c1d] mb-4">Plan Limits</h3>
                <div className="space-y-3">
                  {[
                    { icon: 'store',          label: 'Salons',           val: sub.features.max_salons },
                    { icon: 'group',          label: 'Staff',            val: sub.features.max_staff },
                    { icon: 'content_cut',    label: 'Services',         val: sub.features.max_services },
                    { icon: 'calendar_month', label: 'Bookings / month', val: sub.features.max_bookings_per_month },
                  ].filter(r => r.val != null).map(row => (
                    <div key={row.label} className="flex items-center justify-between">
                      <div className="flex items-center gap-2 text-ds-on-surface-variant">
                        <span className="material-symbols-outlined text-sm text-ds-outline">{row.icon}</span>
                        <span className="text-sm">{row.label}</span>
                      </div>
                      <span className="text-sm font-bold text-ds-primary">{row.val}</span>
                    </div>
                  ))}
                  {[
                    { icon: 'calendar_month', label: 'Calendar',        val: sub.features.calendar },
                    { icon: 'bar_chart',     label: 'Analytics',       val: sub.features.analytics },
                    { icon: 'support_agent', label: 'Priority support', val: sub.features.priority_support },
                  ].map(row => (
                    <div key={row.label} className="flex items-center justify-between">
                      <div className="flex items-center gap-2 text-ds-on-surface-variant">
                        <span className="material-symbols-outlined text-sm text-ds-outline">{row.icon}</span>
                        <span className="text-sm">{row.label}</span>
                      </div>
                      <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${row.val ? 'bg-ds-primary/10 text-ds-primary' : 'bg-[#e1e3e4] text-ds-outline'}`}>
                        {row.val ? 'Included' : 'Not included'}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ── Analytics view ── */}
      {mainTab === 'analytics' && !hasAnalytics && (
        <div className="bg-white rounded-lg p-16 text-center">
          <div className="w-16 h-16 rounded-full bg-[#e1e3e4] flex items-center justify-center mx-auto mb-5">
            <span className="material-symbols-outlined text-3xl text-ds-outline">lock</span>
          </div>
          <h3 className="font-headline text-xl font-bold text-[#191c1d] mb-2">Analytics Locked</h3>
          <p className="text-sm text-ds-outline max-w-sm mx-auto mb-6">
            Upgrade your subscription plan to unlock revenue charts, daily trends, top services, profit breakdowns, and more.
          </p>
          <a href="/owner/subscription"
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-ds-primary text-white text-sm font-bold hover:bg-ds-primary-container transition-colors">
            <span className="material-symbols-outlined text-base">upgrade</span>
            Upgrade Plan
          </a>
        </div>
      )}

      {mainTab === 'analytics' && hasAnalytics && (
        <AnalyticsView salons={salons} />
      )}
    </div>
  );
}
