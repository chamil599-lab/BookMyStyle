import { useState, useEffect, useCallback, useMemo, memo } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

const NAV_ITEMS = [
  { path: "/", label: "Home" },
  { path: "/salons", label: "Salons" },
  { path: "/services", label: "Services" },
  { path: "/deals", label: "Deals" },
  { path: "/about", label: "About" },
];

const getRolePaths = (role) => {
  if (role === 'admin') {
    return {
      dashboard: '/admin',
      profile: '/admin/profile',
      bookings: '/admin/bookings',
      bookingsLabel: 'Bookings',
    };
  }

  if (role === 'owner') {
    return {
      dashboard: '/owner',
      profile: '/owner/profile',
      bookings: '/owner/bookings',
      bookingsLabel: 'Bookings',
    };
  }

  return {
    dashboard: '/dashboard',
    profile: '/dashboard/profile',
    bookings: '/dashboard/upcoming',
    bookingsLabel: 'My Bookings',
  };
};

const ROLE_LABEL = { admin: 'Admin', owner: 'Owner', customer: 'Customer' };

const BrandLogo = memo(function BrandLogo({ scrolled = false, mobile = false }) {
  if (mobile) {
    return (
      <Link to="/" className="relative z-10 mb-6 inline-flex items-center gap-3">
        <span className="flex h-12 w-12 items-center justify-center rounded-lg bg-white/12 shadow-[0_14px_30px_rgba(0,0,0,0.18)]">
          <img src="/logo2.png" alt="BookMyStyle" className="h-10 w-10 rounded-md object-contain" />
        </span>
        <span>
          <span className="block font-headline text-lg font-black tracking-tight text-white">BookMyStyle</span>
          <span className="text-[10px] font-bold uppercase tracking-[0.22em] text-[#e0b15d]">Beauty Booking</span>
        </span>
      </Link>
    );
  }

  return (
    <Link to="/" className="group inline-flex items-center gap-3">
      <span className="flex h-12 w-12 items-center justify-center transition-all duration-300 group-hover:-translate-y-0.5 group-hover:scale-105">
        <img src="/logo2.png" alt="BookMyStyle" className="h-10 w-10 object-contain" />
      </span>
      <span>
        <span
          className={`block font-headline text-lg font-black tracking-tight transition-colors duration-300 ${
            scrolled ? 'text-slate-200/90' : 'text-slate-200/90'
          }`}
        >
          BookMyStyle
        </span>
        <span className="text-[10px] font-bold uppercase tracking-[0.22em] text-(--terra)">
          Beauty Booking
        </span>
      </span>
    </Link>
  );
});

// Memoized NavLink component
const NavLink = memo(function NavLink({ item, isActive, onHero }) {
  return (
    <Link
      to={item.path}
      className={`relative px-4 py-2 text-[11px] font-medium tracking-[0.16em] uppercase transition-all duration-300 group ${
        isActive
          ? onHero
            ? "text-white bg-(--terra)"
            : "text-white bg-(--terra)"
          : onHero
          ? "text-slate-200/90 hover:text-(--terra)"
          : "text-slate-200/90 hover:text-(--terra)"
      }`}
    >
      {item.label}
      <span
        className={`absolute inset-0 transition-opacity duration-300 ${
          isActive
            ? onHero
              ? "bg-(--terra) opacity-20"
              : "bg-(--cream) opacity-15"
            : onHero
            ? "bg-(--terra) opacity-0 group-hover:opacity-10"
            : "bg-(--terra) opacity-0 group-hover:opacity-10"
        }`}
      />
    </Link>
  );
});

// Memoized UserMenuDropdown component
const UserMenuDropdown = memo(function UserMenuDropdown({ onClose, onLogout, user, userInitial }) {
  const rolePaths = getRolePaths(user?.role);
  const roleLabel = ROLE_LABEL[user?.role] ?? 'Account';

  const menuItems = [
    { path: rolePaths.dashboard, label: 'Dashboard' },
    { path: rolePaths.profile, label: 'Profile' },
    { path: rolePaths.bookings, label: rolePaths.bookingsLabel },
  ];

  return (
    <div className="absolute right-0 mt-2 overflow-hidden bg-white border border-gray-200 shadow-lg w-52 rounded-xl">
      {/* User identity header */}
      <div className="flex items-center gap-3 px-4 py-3 bg-(--cream)/60 border-b border-gray-100">
        <div className="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-(--terra) shrink-0">
          {userInitial}
        </div>
        <div className="min-w-0">
          <p className="text-sm font-semibold text-(--ink) truncate">{user?.name}</p>
          <p className="text-[10px] text-(--muted) font-medium uppercase tracking-widest">{roleLabel}</p>
        </div>
      </div>

      <div className="py-1">
        {menuItems.map((item) => (
          <Link
            key={item.path}
            to={item.path}
            onClick={onClose}
            className="block px-4 py-2 text-sm text-(--ink) hover:bg-(--cream) hover:text-(--terra) transition-colors"
          >
            {item.label}
          </Link>
        ))}
      </div>

      <div className="border-t border-gray-100">
        <button
          onClick={onLogout}
          className="block w-full px-4 py-2 text-sm text-left text-red-600 transition-colors hover:bg-red-50"
        >
          Logout
        </button>
      </div>
    </div>
  );
});

// Memoized MobileNavLink component
const MobileNavLink = memo(function MobileNavLink({ item, isActive, onClose }) {
  return (
    <Link
      to={item.path}
      onClick={onClose}
      className={`flex items-center justify-between rounded-r-2xl border-l-4 px-4 py-3.5 transition-all duration-200 ${
        isActive
          ? "border-(--terra) bg-[linear-gradient(90deg,rgba(196,98,45,0.16),rgba(247,240,230,0.95))] text-(--ink) shadow-xs"
          : "border-transparent text-(--ink) hover:border-[rgba(196,98,45,0.45)] hover:bg-(--cream)/80"
      }`}
    >
      <span className="font-[family-name:Unbounded] text-[11px] font-semibold tracking-[0.2em] uppercase">
        {item.label}
      </span>
      {isActive && <span className="h-2 w-2 rounded-full bg-(--terra)" />}
    </Link>
  );
});

// Memoized HamburgerIcon component
const HamburgerIcon = memo(function HamburgerIcon({ isOpen }) {
  return (
    <div className="flex flex-col items-center justify-center w-6 h-6">
      <span
        className={`block h-0.5 w-6 bg-current transform transition duration-300 ${
          isOpen ? "rotate-45 translate-y-0.5" : "-translate-y-1"
        }`}
      />
      <span
        className={`block h-0.5 w-6 bg-current transition-all duration-300 ${
          isOpen ? "opacity-0" : "opacity-100"
        }`}
      />
      <span
        className={`block h-0.5 w-6 bg-current transform transition duration-300 ${
          isOpen ? "-rotate-45 -translate-y-0.5" : "translate-y-1"
        }`}
      />
    </div>
  );
});

// Memoize current year to avoid recalculation
const CURRENT_YEAR = new Date().getFullYear();

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout, isAuthenticated } = useAuth();
  const rolePaths = useMemo(() => getRolePaths(user?.role), [user?.role]);
  // Memoized user initial
  const userInitial = useMemo(
    () => user?.name?.charAt(0).toUpperCase() ?? "",
    [user?.name]
  );

  // Handle scroll effect with requestAnimationFrame throttling
  useEffect(() => {
    let ticking = false;
    const handleScroll = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          setScrolled(window.scrollY > 10);
          ticking = false;
        });
        ticking = true;
      }
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  // Close menus when route changes
  useEffect(() => {
    setIsOpen(false);
    setShowUserMenu(false);
  }, [location.pathname]);

  // Memoized callbacks
  const handleLogout = useCallback(async () => {
    await logout();
    setShowUserMenu(false);
    navigate("/");
  }, [logout, navigate]);

  const closeUserMenu = useCallback(() => setShowUserMenu(false), []);
  const toggleUserMenu = useCallback(() => setShowUserMenu((prev) => !prev), []);
  const toggleMobileMenu = useCallback(() => setIsOpen((prev) => !prev), []);
  const closeMobileMenu = useCallback(() => setIsOpen(false), []);

  const isSalonDetail = /^\/salon\/[^/]+/.test(location.pathname);

  return (
    <nav
      className={`fixed w-full z-50 top-0 left-0 transition-all duration-500 ${
        isSalonDetail ? "hidden md:block" : ""
      } ${
        scrolled
          ? "bg-[rgba(26,15,10,0.92)] backdrop-blur-md shadow-lg py-2 border-b border-white/10"
          : "bg-transparent shadow-none py-6"
      }`}
    >
      <div className="w-full px-4 sm:px-8 lg:px-12">
        <div className="flex items-center justify-between">
          {/* Logo + Name */}
          <BrandLogo scrolled={scrolled} />

          {/* Desktop Menu */}
          <div className="items-center hidden space-x-1 lg:flex">
            {NAV_ITEMS.map((item) => (
              <NavLink
                key={item.path}
                item={item}
                isActive={location.pathname === item.path}
                onHero={!scrolled}
              />
            ))}

            {/* Auth Buttons */}
            <div
              className={`flex items-center space-x-3 ml-4 pl-4 transition-colors duration-300 ${
                scrolled ? "border-l border-slate-200/30" : "border-l border-slate-200/30"
              }`}
            >
              {isAuthenticated ? (
                <div className="relative">
                  <button
                    onClick={toggleUserMenu}
                    className="flex items-center px-3 py-2 space-x-2 transition-all duration-300 hover:bg-(--cream)/20"
                  >
                    <div className="flex items-center justify-center w-8 h-8 text-sm font-medium text-white rounded-full bg-(--terra)">
                      {userInitial}
                    </div>
                    <span className={`text-sm font-medium transition-colors duration-300 ${!scrolled ? "text-slate-200/90" : "text-slate-200/90"}`}>
                      {user?.name}
                    </span>
                    <svg
                      className={`w-4 h-4 transition-transform duration-300 ${showUserMenu ? "rotate-180" : ""} ${!scrolled ? "text-slate-200/90" : "text-slate-200/70"}`}
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M19 9l-7 7-7-7"
                      />
                    </svg>
                  </button>

                  {showUserMenu && (
                    <UserMenuDropdown
                      onClose={closeUserMenu}
                      onLogout={handleLogout}
                      user={user}
                      userInitial={userInitial}
                    />
                  )}
                </div>
              ) : (
                <Link to="/login">
                  <button
                    className={`px-4 py-2 text-sm font-medium transition-all duration-300 border ${
                      scrolled
                        ? "text-slate-200/90 border-slate-200/40 hover:bg-white/10"
                        : "text-slate-200/90 border-slate-200/40 hover:bg-white/10"
                    }`}
                  >
                    Sign In
                  </button>
                </Link>
              )}

            </div>
          </div>

          {/* Mobile Menu Button */}
          <div className="flex items-center space-x-3 lg:hidden">
            <button
              onClick={toggleMobileMenu}
              className={`p-2 rounded-lg transition-colors duration-300 ${
                scrolled
                  ? "text-slate-200/90 hover:bg-white/10"
                  : "text-slate-200/90 hover:bg-white/10"
              }`}
              aria-label="Toggle menu"
              aria-expanded={isOpen}
            >
              <HamburgerIcon isOpen={isOpen} />
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Dropdown Menu */}
      <div
        className={`fixed inset-0 z-60 lg:hidden ${isOpen ? "" : "pointer-events-none"}`}
      >
        {/* Backdrop */}
        <div
          className={`absolute inset-0 bg-black/50 backdrop-blur-xs transition-opacity duration-300 ${
            isOpen ? "opacity-100" : "opacity-0"
          }`}
          onClick={closeMobileMenu}
          aria-hidden="true"
        />

        {/* Menu Panel */}
        <div
          className={`absolute right-0 top-0 h-screen w-[88vw] max-w-[360px] flex flex-col overflow-hidden shadow-[0_0_80px_rgba(0,0,0,0.35)] transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] ${
            isOpen ? "translate-x-0" : "translate-x-full"
          }`}
        >
          {/* Gradient Header */}
          <div className="relative bg-(--terra) px-6 pt-12 pb-8 overflow-hidden">
            {/* Decorative circles */}
            <div className="absolute rounded-full -top-8 -right-8 w-36 h-36 bg-white/5" />
            <div className="absolute w-20 h-20 rounded-full top-4 -right-4 bg-white/5" />
            <div className="absolute rounded-full -bottom-6 -left-6 w-28 h-28 bg-black/10" />

            {/* Close button */}
            <button
              onClick={closeMobileMenu}
              className="absolute p-2 text-white transition-colors rounded-full top-4 right-4 bg-white/10 hover:bg-white/20"
              aria-label="Close menu"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            {/* Brand */}
            <BrandLogo mobile />

            {/* User info or greeting */}
            {isAuthenticated ? (
              <div className="relative z-10 flex items-center gap-3">
                <div className="flex items-center justify-center w-11 h-11 rounded-full bg-white/20 border-2 border-white/30 font-[family-name:Unbounded] text-sm font-bold text-white">
                  {userInitial}
                </div>
                <div>
                  <p className="text-sm font-semibold leading-tight text-white">{user?.name}</p>
                  <p className="text-white/60 text-[11px] mt-0.5">{user?.email}</p>
                </div>
              </div>
            ) : (
              <p className="text-white/70 text-[11px] tracking-wide relative z-10">
                Discover beauty, book effortlessly.
              </p>
            )}
          </div>

          {/* Nav body */}
          <div className="flex-1 min-h-0 flex flex-col overflow-y-auto bg-(--white)">

            {/* Navigation Items */}
            <div className="px-4 pt-5 pb-3 space-y-1">
              <span className="block font-[family-name:Unbounded] text-[8px] font-semibold tracking-[0.4em] text-(--muted) uppercase px-2 mb-3">
                Menu
              </span>
              {NAV_ITEMS.map((item) => (
                <MobileNavLink
                  key={item.path}
                  item={item}
                  isActive={location.pathname === item.path}
                  onClose={closeMobileMenu}
                />
              ))}
            </div>

            {/* Spacer */}
            <div className="flex-1" />

            {/* Divider */}
            <div className="mx-5 h-px bg-[rgba(196,98,45,0.15)]" />

            {/* Auth + Book Section */}
            <div className="px-5 py-5 space-y-2.5">
              {isAuthenticated ? (
                <>
                  <Link to={rolePaths.dashboard} onClick={closeMobileMenu}>
                    <button className="w-full px-6 py-3.5 rounded-xl font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.15em] text-white uppercase transition-all duration-200 bg-(--ink) hover:bg-(--terra) active:scale-[0.98]">
                      Dashboard
                    </button>
                  </Link>
                  <Link to={rolePaths.profile} onClick={closeMobileMenu}>
                    <button className="w-full px-6 py-3.5 rounded-xl font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.15em] uppercase transition-all duration-200 border border-[rgba(30,23,16,0.12)] bg-(--cream)/75 text-(--ink) hover:border-(--terra)/35 hover:text-(--terra) hover:bg-(--cream) active:scale-[0.98]">
                      Profile
                    </button>
                  </Link>
                  <Link to={rolePaths.bookings} onClick={closeMobileMenu}>
                    <button className="w-full px-6 py-3.5 rounded-xl font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.15em] uppercase transition-all duration-200 border border-[rgba(30,23,16,0.12)] bg-white text-(--ink) hover:border-(--terra)/35 hover:text-(--terra) hover:bg-(--cream)/60 active:scale-[0.98]">
                      {rolePaths.bookingsLabel}
                    </button>
                  </Link>
                  <button
                    onClick={handleLogout}
                    className="w-full px-6 py-3.5 rounded-xl font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.15em] uppercase transition-all duration-200 border border-[rgba(30,23,16,0.25)] text-(--muted) hover:border-red-300 hover:text-red-600 hover:bg-red-50 active:scale-[0.98]"
                  >
                    Sign Out
                  </button>
                </>
              ) : (
                <Link to="/login" onClick={closeMobileMenu}>
                  <button className="w-full px-6 py-3.5 rounded-xl font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.15em] text-white uppercase transition-all duration-200 bg-(--ink) hover:bg-(--terra) active:scale-[0.98]">
                    Sign In
                  </button>
                </Link>
              )}

            </div>

            {/* Footer */}
            <div className="px-5 py-3 border-t border-[rgba(196,98,45,0.12)] bg-(--sand)">
              <div className="flex items-center justify-between">
                <p className="font-[family-name:Unbounded] text-[8px] font-semibold tracking-[0.25em] text-(--muted) uppercase">
                  © {CURRENT_YEAR} Book My Style
                </p>
                <div className="flex gap-1">
                  <span className="h-0.5 w-4 rounded-full bg-(--terra)/40" />
                  <span className="h-0.5 w-2 rounded-full bg-(--terra)" />
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;

