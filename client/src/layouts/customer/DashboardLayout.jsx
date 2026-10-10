import { useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Helmet } from 'react-helmet-async';
import ThemeToggle from '../../components/ThemeToggle';
import NotificationBell from '../../components/NotificationBell';

const navGroups = [
  {
    items: [
      { path: '/dashboard', label: 'Dashboard', icon: 'dashboard', end: true },
    ],
  },
  {
    label: 'Booking Management',
    items: [
      { path: '/dashboard/upcoming', label: 'Upcoming', icon: 'calendar_month', end: false },
      { path: '/dashboard/history', label: 'History', icon: 'history', end: false },
    ],
  },
  {
    label: 'Saved & Account',
    items: [
      { path: '/dashboard/favorites', label: 'Favorites', icon: 'favorite', end: false },
      { path: '/dashboard/profile', label: 'Profile', icon: 'person', end: false },
      { path: '/dashboard/change-password', label: 'Password', icon: 'lock', end: false },
    ],
  },
];

export default function DashboardLayout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const initials = user?.name
    ? user.name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
    : 'C';

  return (
    <div className="ds-shell">
      <Helmet>
        <title>Customer Dashboard - BookMyStyle</title>
      </Helmet>

      <aside className={`fixed left-0 top-0 bottom-0 z-40 hidden h-full flex-col border-r border-white/10 bg-[linear-gradient(180deg,#032f2f_0%,#004242_52%,#0c5455_100%)] shadow-[18px_0_40px_rgba(3,47,47,0.24)] transition-all duration-300 md:flex ${sidebarCollapsed ? 'w-24' : 'w-72'}`}>
        <div className={`p-6 flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3'}`}>
          <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10">
            <span className="material-symbols-outlined text-xl text-[#d6f1f0]">person</span>
          </div>
          {!sidebarCollapsed && (
            <div>
              <p className="font-headline text-base font-black leading-tight text-white">Beauty Dashboard</p>
              <p className="text-xs text-[#9fd0cf]">Customer View</p>
            </div>
          )}
        </div>

        <nav className="mt-2 flex-1 overflow-y-auto">
          {navGroups.map((group, gi) => (
            <div key={gi} className={gi > 0 ? 'mt-7' : ''}>
              {group.label && !sidebarCollapsed && (
                <p className="mb-1 px-8 text-[10px] font-bold uppercase tracking-widest text-[#c9ece9]/90">
                  {group.label}
                </p>
              )}
              <div className="space-y-0.5">
                {group.items.map((item) => {
                  const isActive = item.end
                    ? location.pathname === item.path
                    : location.pathname.startsWith(item.path);

                  return (
                    <div key={item.path} className="px-4">
                      <Link
                        to={item.path}
                        title={sidebarCollapsed ? item.label : undefined}
                        className={`flex items-center px-4 py-3 text-sm font-medium transition-all ${
                          sidebarCollapsed ? 'justify-center rounded-2xl' : 'gap-4 rounded-r-full'
                        } ${
                          isActive
                            ? 'bg-white font-semibold text-[#004242] shadow-[0_10px_24px_rgba(0,0,0,0.18)]'
                            : 'text-[#eefcfb] hover:bg-white/10 hover:text-white'
                        }`}
                      >
                        <span className="material-symbols-outlined text-xl">{item.icon}</span>
                        {!sidebarCollapsed && <span>{item.label}</span>}
                      </Link>
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        <div className="border-t border-white/10 p-6">
          <div className={`flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3'}`}>
            <Link
              to="/dashboard/profile"
              title={sidebarCollapsed ? user?.name : undefined}
              className={`group flex min-w-0 items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3 flex-1'}`}
            >
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,#0e7b7c_0%,#1ab8b9_100%)] text-sm font-bold text-white shadow-[0_8px_20px_rgba(14,123,124,0.45)] ring-1 ring-white/20 transition-all group-hover:ring-2 group-hover:ring-[#8bd0d1]/60">
                {initials}
              </div>
              {!sidebarCollapsed && (
                <div className="min-w-0">
                  <p className="truncate text-sm font-bold text-white transition-colors group-hover:text-[#8bd0d1]">{user?.name}</p>
                  <p className="text-xs font-semibold tracking-wide text-[#8bd0d1]">Customer</p>
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

      <main className={`flex min-h-screen flex-col bg-[linear-gradient(180deg,#eef7f6_0%,#f7fbfb_100%)] transition-all duration-300 ${sidebarCollapsed ? 'md:ml-24' : 'md:ml-72'}`}>
        <header className="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-[#004242]/10 bg-[#eef7f6]/95 px-6 backdrop-blur">
          <div className="flex items-center gap-4">
            <button
              type="button"
              onClick={() => setSidebarCollapsed((v) => !v)}
              className="hidden h-10 w-10 items-center justify-center rounded-2xl bg-white text-[#004242] shadow-[0_10px_25px_rgba(0,66,66,0.08)] transition-colors hover:bg-[#dff4f2] md:flex"
              title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            >
              <span className="material-symbols-outlined text-[20px]">
                {sidebarCollapsed ? 'keyboard_double_arrow_right' : 'keyboard_double_arrow_left'}
              </span>
            </button>
            <h1 className="font-headline text-xl font-bold tracking-tight text-[#004242]">BookMyStyle</h1>
            <span className="rounded-full bg-[#004242] px-2 py-0.5 text-xs font-semibold text-white shadow-[0_6px_18px_rgba(0,66,66,0.16)]">Customer</span>
          </div>

          <div className="mx-8 hidden max-w-md flex-1 sm:block">
            <div className="relative flex items-center">
              <span className="material-symbols-outlined absolute left-3 text-base text-[#6f7979]">search</span>
              <input
                className="w-full rounded-full border border-[#004242]/10 bg-white py-2 pl-10 pr-4 text-sm font-body transition-all focus:outline-none focus:ring-2 focus:ring-[#004242]/20"
                placeholder="Search dashboard..."
                type="text"
              />
            </div>
          </div>

          <div className="flex items-center gap-3">
            <ThemeToggle />
            <NotificationBell />
            <Link
              to="/salons"
              className="hidden rounded-full border border-[#004242]/10 bg-white px-4 py-2 text-sm font-semibold text-[#004242] transition hover:bg-[#dff4f2] sm:inline-flex"
            >
              Explore
            </Link>
            <button
              onClick={handleLogout}
              title="Logout"
              aria-label="Logout"
              className="flex h-10 w-10 items-center justify-center rounded-full text-[#004242] transition-colors hover:bg-[#dff4f2] md:hidden"
            >
              <span className="material-symbols-outlined text-xl">logout</span>
            </button>
            <Link to="/dashboard/profile" className="group hidden items-center gap-2.5 md:flex rounded-full px-2 py-1 transition-colors hover:bg-[#dff4f2]">
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#004242] text-xs font-bold text-white transition-all group-hover:ring-2 group-hover:ring-[#004242]/30">
                {initials}
              </div>
              <div className="flex flex-col items-end">
                <span className="font-body text-xs font-bold text-[#004242]">{user?.name?.split(' ')[0]}</span>
                <span className="font-body text-[10px] uppercase tracking-widest text-[#6f7979]">Customer</span>
              </div>
            </Link>
          </div>
        </header>

        <div className="mx-auto flex-1 w-full max-w-7xl p-6 pb-24 md:p-10 md:pb-10">
          <Outlet />
        </div>
      </main>

      <nav className="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around border-t border-[#bec8c8]/20 bg-[#f8f9fa]/80 px-4 pb-6 pt-2 shadow-[0_-4px_24px_rgba(0,0,0,0.04)] backdrop-blur-xl md:hidden">
        {[
          { path: '/dashboard', icon: 'home', label: 'Home' },
          { path: '/dashboard/upcoming', icon: 'event_note', label: 'Upcoming' },
          { path: '/dashboard/history', icon: 'history', label: 'History' },
          { path: '/dashboard/favorites', icon: 'favorite', label: 'Saved' },
          { path: '/dashboard/profile', icon: 'account_circle', label: 'Profile' },
        ].map((item) => {
          const isActive = location.pathname === item.path;
          return (
            <Link
              key={item.path}
              to={item.path}
              className={`flex flex-col items-center justify-center rounded-xl p-2 px-4 text-[10px] font-medium uppercase tracking-wider transition-all ${
                isActive ? 'bg-[#005b5c] text-white' : 'text-[#566060]'
              }`}
            >
              <span className="material-symbols-outlined mb-1 text-lg">{item.icon}</span>
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>
    </div>
  );
}
