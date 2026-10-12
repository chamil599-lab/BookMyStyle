import { useEffect, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { Helmet } from 'react-helmet-async';
import ThemeToggle from '../components/ThemeToggle';
import NotificationBell from '../components/NotificationBell';
import api from '../utils/api';

const navGroups = [
  {
    items: [
      { path: '/owner', label: 'Dashboard', icon: 'dashboard', end: true },
    ],
  },
  {
    label: 'Salon Management',
    items: [
      { path: '/owner/salons',    label: 'My Salons', icon: 'store',       end: false },
      { path: '/owner/services',  label: 'Services',  icon: 'content_cut', end: false },
    ],
  },
  {
    label: 'Booking Management',
    items: [
      { path: '/owner/bookings', label: 'Bookings', icon: 'calendar_month', end: false },
    ],
  },
  {
    label: 'Billing',
    items: [
      { path: '/owner/subscription', label: 'Subscription', icon: 'card_membership', end: false },
    ],
  },
];

const ALLOWED_WHEN_INACTIVE = ['/owner/subscription', '/owner/profile'];

const isPathAllowedWhenInactive = (pathname) =>
  ALLOWED_WHEN_INACTIVE.some((p) => pathname === p || pathname.startsWith(`${p}/`));

export default function OwnerLayout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  // Single source of truth for the gate. React Query caches the response so
  // navigation between pages doesn't refetch; SubscribeModal can invalidate
  // ['owner-subscription-state'] to force a refresh after subscribing.
  const { data: subData, isLoading: subLoading } = useQuery({
    queryKey: ['owner-subscription-state'],
    queryFn: () => api.get('/owner/subscription/packages').then((r) => r.data),
    staleTime: 5 * 60 * 1000,        // 5 min — gate state changes rarely
    gcTime: 10 * 60 * 1000,
    refetchOnWindowFocus: false,
  });

  const subscriptionActive = (() => {
    if (subLoading || !subData) return null; // unknown
    const current = subData.current;
    return Boolean(
      current &&
        current.status === 'active' &&
        typeof current.days_remaining === 'number' &&
        current.days_remaining > 0,
    );
  })();

  // Redirect inactive owners to the subscription page (except profile & subscription itself).
  useEffect(() => {
    if (subscriptionActive === false && !isPathAllowedWhenInactive(location.pathname)) {
      navigate('/owner/subscription', { replace: true });
    }
  }, [subscriptionActive, location.pathname, navigate]);

  const navDisabled = subscriptionActive === false;

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const initials = user?.name
    ? user.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
    : 'O';

  return (
    <div className="ds-shell">
      <Helmet>
        <title>Owner Dashboard — BookMyStyle</title>
      </Helmet>

      {/* ── Sidebar ── */}
      <aside className={`fixed left-0 top-0 bottom-0 z-40 hidden md:flex flex-col h-full border-r border-white/10 bg-[linear-gradient(180deg,#032f2f_0%,#004242_52%,#0c5455_100%)] shadow-[18px_0_40px_rgba(3,47,47,0.24)] transition-all duration-300 ${sidebarCollapsed ? 'w-24' : 'w-72'}`}>
        {/* Logo */}
        <div className={`p-6 flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3'}`}>
          <div className="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center">
            <span className="material-symbols-outlined text-[#d6f1f0] text-xl">storefront</span>
          </div>
          {!sidebarCollapsed && (
            <div>
              <p className="font-headline text-white font-black text-base leading-tight">Salon Manager</p>
              <p className="text-xs text-[#c9ece9]">Owner View</p>
            </div>
          )}
        </div>

        {/* Nav */}
        <nav className="mt-2 flex-1 overflow-y-auto">
          {navGroups.map((group, gi) => (
            <div key={gi} className={gi > 0 ? 'mt-7' : ''}>
              {group.label && !sidebarCollapsed && (
                <p className="px-8 mb-1 text-[10px] font-bold uppercase tracking-widest text-[#c9ece9]/90">
                  {group.label}
                </p>
              )}
              <div className="space-y-0.5">
                {group.items.map(item => {
                  const isActive = item.end
                    ? location.pathname === item.path
                    : location.pathname.startsWith(item.path);
                  const isLocked = navDisabled && !isPathAllowedWhenInactive(item.path);
                  const commonClasses = `flex items-center px-4 py-3 font-medium text-sm transition-all ${
                    sidebarCollapsed ? 'justify-center rounded-2xl' : 'gap-4 rounded-r-full'
                  }`;
                  return (
                    <div key={item.path} className="px-4">
                      {isLocked ? (
                        <div
                          title={sidebarCollapsed ? `${item.label} (locked)` : undefined}
                          aria-disabled="true"
                          className={`${commonClasses} text-[#eefcfb]/40 cursor-not-allowed`}
                        >
                          <span className="material-symbols-outlined text-xl">{item.icon}</span>
                          {!sidebarCollapsed && (
                            <span className="flex-1 flex items-center justify-between">
                              <span>{item.label}</span>
                              <span className="material-symbols-outlined text-base text-[#eefcfb]/40">lock</span>
                            </span>
                          )}
                        </div>
                      ) : (
                        <Link
                          to={item.path}
                          title={sidebarCollapsed ? item.label : undefined}
                          className={`${commonClasses} ${
                            isActive
                              ? 'bg-white text-ds-primary font-semibold shadow-[0_10px_24px_rgba(0,0,0,0.18)]'
                              : 'text-[#eefcfb] hover:text-white hover:bg-white/10'
                          }`}
                        >
                          <span className="material-symbols-outlined text-xl">{item.icon}</span>
                          {!sidebarCollapsed && <span>{item.label}</span>}
                        </Link>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        {/* User profile */}
        <div className="p-6 border-t border-white/10">
          <div className={`flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3'}`}>
            <Link to="/owner/profile" title={sidebarCollapsed ? user?.name : undefined} className={`flex items-center min-w-0 group ${sidebarCollapsed ? 'justify-center' : 'gap-3 flex-1'}`}>
              <div className="w-10 h-10 rounded-full bg-[linear-gradient(135deg,#0e7b7c_0%,#1ab8b9_100%)] flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-[0_8px_20px_rgba(14,123,124,0.45)] ring-1 ring-white/20 group-hover:ring-2 group-hover:ring-ds-on-primary-container/60 transition-all">
                {initials}
              </div>
              {!sidebarCollapsed && (
                <div className="min-w-0">
                  <p className="text-sm font-bold text-white truncate group-hover:text-ds-on-primary-container transition-colors">{user?.name}</p>
                  <p className="text-xs font-semibold tracking-wide text-ds-on-primary-container">Salon Owner</p>
                </div>
              )}
            </Link>
            <button
              onClick={handleLogout}
              title="Logout"
              className={`${sidebarCollapsed ? 'hidden' : 'ml-auto'} flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/8 text-[#c9ece9] transition-all hover:bg-[#0e7b7c] hover:text-white hover:shadow-[0_6px_16px_rgba(14,123,124,0.4)]`}
            >
              <span className="material-symbols-outlined text-xl">logout</span>
            </button>
          </div>
        </div>
      </aside>

      {/* ── Main wrapper ── */}
      <main className={`flex flex-col min-h-screen bg-[linear-gradient(180deg,#eef7f6_0%,#f7fbfb_100%)] transition-all duration-300 ${sidebarCollapsed ? 'md:ml-24' : 'md:ml-72'}`}>
        {/* Top App Bar */}
        <header className="sticky top-0 z-30 bg-[#eef7f6]/95 backdrop-blur-sm h-16 flex justify-between items-center w-full px-6 border-b border-ds-primary/10">
          <div className="flex items-center gap-4">
            <button
              type="button"
              onClick={() => setSidebarCollapsed(v => !v)}
              className="hidden md:flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-ds-primary shadow-[0_10px_25px_rgba(0,66,66,0.08)] hover:bg-[#dff4f2] transition-colors"
              title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            >
              <span className="material-symbols-outlined text-[20px]">
                {sidebarCollapsed ? 'keyboard_double_arrow_right' : 'keyboard_double_arrow_left'}
              </span>
            </button>
            <h1 className="font-headline text-xl font-bold tracking-tight text-ds-primary">
              BookMyStyle
            </h1>
            <span className="text-xs bg-ds-primary text-white px-2 py-0.5 rounded-full font-semibold font-body shadow-[0_6px_18px_rgba(0,66,66,0.16)]">Owner</span>
          </div>

          <div className="flex items-center gap-3">
            <ThemeToggle />
            <NotificationBell />
            <button
              onClick={handleLogout}
              title="Logout"
              aria-label="Logout"
              className="md:hidden p-2 text-ds-primary hover:bg-[#dff4f2] rounded-full transition-colors"
            >
              <span className="material-symbols-outlined text-xl">logout</span>
            </button>
            <Link to="/owner/profile" className="hidden md:flex items-center gap-2.5 group rounded-full px-2 py-1 transition-colors hover:bg-[#dff4f2]">
              <div className="w-8 h-8 rounded-full bg-ds-primary flex items-center justify-center text-white font-bold text-xs shrink-0 group-hover:ring-2 group-hover:ring-ds-primary/30 transition-all">
                {initials}
              </div>
              <div className="flex flex-col items-end">
                <span className="text-xs text-ds-primary font-bold font-body">{user?.name?.split(' ')[0]}</span>
                <span className="text-[10px] text-ds-outline uppercase tracking-widest font-body">Owner</span>
              </div>
            </Link>
          </div>
        </header>

        {/* Page content */}
        <div className="flex-1 p-6 md:p-10 max-w-7xl mx-auto w-full pb-24 md:pb-10">
          <Outlet />
        </div>
      </main>

      {/* Bottom nav — mobile only */}
      <nav className="fixed bottom-0 left-0 w-full z-50 md:hidden flex justify-around items-center px-4 pb-6 pt-2 bg-[#f8f9fa]/80 backdrop-blur-xl border-t border-ds-outline-variant/20 shadow-[0_-4px_24px_rgba(0,0,0,0.04)]">
        {[
          { path: '/owner',               icon: 'home',            label: 'Home' },
          { path: '/owner/bookings',      icon: 'event_note',      label: 'Bookings' },
          { path: '/owner/salons',        icon: 'store',           label: 'Salons' },
          { path: '/owner/subscription',  icon: 'card_membership', label: 'Plans' },
          { path: '/owner/profile',       icon: 'account_circle',  label: 'Profile' },
        ].map((item) => {
          const isActive = location.pathname === item.path;
          const isLocked = navDisabled && !isPathAllowedWhenInactive(item.path);
          const baseClasses = 'flex flex-col items-center justify-center p-2 px-4 text-[10px] font-medium uppercase tracking-wider transition-all rounded-xl';
          if (isLocked) {
            return (
              <div
                key={item.path}
                aria-disabled="true"
                className={`${baseClasses} text-[#566060]/40 cursor-not-allowed`}
              >
                <span className="material-symbols-outlined text-lg mb-1">{item.icon}</span>
                <span>{item.label}</span>
              </div>
            );
          }
          return (
            <Link
              key={item.path}
              to={item.path}
              className={`${baseClasses} ${
                isActive ? 'bg-ds-primary-container text-white' : 'text-[#566060]'
              }`}
            >
              <span className="material-symbols-outlined text-lg mb-1">{item.icon}</span>
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>
    </div>
  );
}
