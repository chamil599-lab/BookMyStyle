import { memo } from 'react';
import { Link, useLocation } from 'react-router-dom';

const NAV_ITEMS = [
  { path: '/', label: 'Home' },
  { path: '/salons', label: 'Salons' },
  { path: '/services', label: 'Services' },
  { path: '/deals', label: 'Deals' },
  { path: '/about', label: 'About' },
];

const AuthNavbar = memo(function AuthNavbar() {
  const location = useLocation();

  return (
    <nav className="sticky top-0 z-50 bg-(--sand) border-b border-[rgba(196,98,45,0.15)] shadow-xs py-3">
      <div className="max-w-[1550px] mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between">

          {/* Logo + Brand */}
          <Link to="/" className="flex items-center space-x-3 group">
            <div className="flex items-center justify-center w-9 h-9 transition-transform duration-300 group-hover:scale-110">
              <img src="/logo2.png" alt="BookMyStyle" className="w-9 h-9 object-contain" />
            </div>
            <div className="flex flex-col">
              <span className="font-[family-name:Unbounded] text-sm font-bold uppercase tracking-[0.12em] text-(--ink)">
                Book My Style
              </span>
              <span className="text-xs text-(--muted) -mt-1">Beauty Booking</span>
            </div>
          </Link>

          {/* Desktop Nav Links */}
          <div className="hidden lg:flex items-center space-x-1">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`px-4 py-2 rounded-full text-[11px] font-medium tracking-[0.16em] uppercase transition-all duration-300 ${
                  location.pathname === item.path
                    ? 'text-(--ink) bg-(--cream)'
                    : 'text-slate-500 hover:text-(--terra)'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </div>

          {/* CTA */}
          <a
            href="https://bookmystyle.limach.lk/admin"
            target="_blank"
            rel="noopener noreferrer"
          >
            <button className="px-5 py-2 text-[11px] font-[family-name:Unbounded] font-bold tracking-[0.15em] uppercase rounded-full bg-(--terra) text-white hover:bg-(--sienna) transition-all duration-300 hover:-translate-y-0.5 shadow-md">
              Book Now
            </button>
          </a>

        </div>
      </div>
    </nav>
  );
});

export default AuthNavbar;
