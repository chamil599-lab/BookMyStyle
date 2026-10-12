import { Link } from 'react-router-dom';

const quickLinks = [
  { label: 'Salons', to: '/salons' },
  { label: 'Services', to: '/services' },
  { label: 'Deals', to: '/deals' },
  { label: 'About', to: '/about' },
];

const supportLinks = [
  { label: 'Privacy Policy', to: '/privacy-policy' },
  { label: 'Terms & Conditions', to: '/terms-and-condition' },
  { label: 'Customer Login', to: '/login' },
  { label: 'Join as Owner', to: '/register' },
];

export default function Footer() {
  return (
    <footer className="relative overflow-hidden bg-[#111819] text-white">
      <div className="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#7a3e12_0%,#c4622d_48%,#e0b15d_100%)]" />

      <div className="mx-auto grid max-w-7xl gap-10 px-6 py-12 md:px-10 lg:grid-cols-[1.2fr_0.8fr_0.8fr_1fr] lg:py-14">
        <div>
          <Link to="/" className="inline-flex items-center gap-3">
            <span className="flex h-12 w-12 items-center justify-center md:rounded-2xl bg-white/8 ring-1 ring-white/10">
              <img src="/logo2.png" alt="BookMyStyle" className="h-9 w-9 object-contain" />
            </span>
            <span>
              <span className="block font-headline text-xl font-black tracking-tight text-white">BookMyStyle</span>
              <span className="text-[10px] font-bold uppercase tracking-[0.22em] text-[#e0b15d]">Beauty Booking</span>
            </span>
          </Link>

          <p className="mt-5 max-w-sm text-sm leading-7 text-white/66">
            Discover trusted salons, compare services, and book your next beauty appointment with less waiting and more confidence.
          </p>

          <div className="mt-6 flex flex-wrap gap-3">
            <Link
              to="/salons"
              className="inline-flex items-center gap-2 md:rounded-full bg-[#c4622d] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#d8753f]"
            >
              <span className="material-symbols-outlined text-[18px]">calendar_month</span>
              Book Now
            </Link>
            <Link
              to="/register"
              className="inline-flex items-center gap-2 md:rounded-full border border-white/14 px-4 py-2 text-sm font-bold text-white/86 transition hover:border-[#e0b15d]/60 hover:bg-white/6"
            >
              <span className="material-symbols-outlined text-[18px]">storefront</span>
              List Salon
            </Link>
          </div>
        </div>

        <div>
          <h2 className="text-xs font-black uppercase tracking-[0.22em] text-[#e0b15d]">Explore</h2>
          <nav className="mt-5 space-y-3">
            {quickLinks.map((link) => (
              <Link key={link.to} to={link.to} className="block text-sm font-semibold text-white/70 transition hover:text-white">
                {link.label}
              </Link>
            ))}
          </nav>
        </div>

        <div>
          <h2 className="text-xs font-black uppercase tracking-[0.22em] text-[#e0b15d]">Support</h2>
          <nav className="mt-5 space-y-3">
            {supportLinks.map((link) => (
              <Link key={link.to} to={link.to} className="block text-sm font-semibold text-white/70 transition hover:text-white">
                {link.label}
              </Link>
            ))}
          </nav>
        </div>

        <div className="md:rounded-lg border border-white/10 bg-white/4 p-5">
          <h2 className="text-xs font-black uppercase tracking-[0.22em] text-[#e0b15d]">Need Help?</h2>
          <div className="mt-5 space-y-4 text-sm text-white/72">
            <p className="flex gap-3">
              <span className="material-symbols-outlined mt-0.5 text-[18px] text-[#e0b15d]">mail</span>
              <a href="mailto:info@bookmystyle.com" className="font-semibold transition hover:text-white">
                info@bookmystyle.com
              </a>
            </p>
            <p className="flex gap-3">
              <span className="material-symbols-outlined mt-0.5 text-[18px] text-[#e0b15d]">location_on</span>
              <span>Sri Lanka salon booking platform</span>
            </p>
            <p className="flex gap-3">
              <span className="material-symbols-outlined mt-0.5 text-[18px] text-[#e0b15d]">verified</span>
              <span>Trusted bookings, owner tools, and customer notifications.</span>
            </p>
          </div>
        </div>
      </div>

      <div className="border-t border-white/10 px-6 py-5 md:px-10">
        <div className="mx-auto flex max-w-7xl flex-col gap-3 text-xs font-semibold text-white/45 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-1">
            <p>© {new Date().getFullYear()} BookMyStyle. All rights reserved.</p>
            <p>
              Powered by{' '}
              <a
                href="https://limach.lk"
                target="_blank"
                rel="noopener noreferrer"
                className="font-bold text-[#e0b15d] transition hover:text-white"
              >
                Limach Web Solutions
              </a>
            </p>
          </div>
          <p className="uppercase tracking-[0.18em]">Book salons faster. Arrive prettier.</p>
        </div>
      </div>
    </footer>
  );
}
