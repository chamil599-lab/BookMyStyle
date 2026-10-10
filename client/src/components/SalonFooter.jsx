import { Link } from 'react-router-dom';

const SalonFooter = ({ salon }) => {
  const navItems = [
    { label: 'Home', path: '/' },
    { label: 'Salons', path: '/salons' },
    { label: 'Services', path: '/services' },
    { label: 'Deals', path: '/deals' },
    { label: 'About Us', path: '/about' },
  ];

  const contactItems = [
    salon.location && { label: 'Location', text: salon.location },
    salon.phone && { label: 'Phone', text: salon.phone },
    salon.email && { label: 'Email', text: salon.email },
    (salon.open_time && salon.close_time) && { label: 'Hours', text: `${salon.open_time} - ${salon.close_time}` },
  ].filter(Boolean);

  const socialItems = [
    salon.facebook_page && { icon: 'facebook', link: salon.facebook_page },
    salon.instagram_page && { icon: 'instagram', link: salon.instagram_page },
    salon.tiktok_page && { icon: 'tiktok', link: salon.tiktok_page },
  ].filter(Boolean);

  return (
    <footer className="mt-16 border-t-2 border-[var(--cream)] bg-[var(--white)]">
      <div className="mx-auto max-w-7xl px-6 py-12">
        <div className="mb-8 grid grid-cols-1 gap-8 md:grid-cols-3">
          <div>
            <div className="mb-3 inline-flex bg-[var(--terra)] px-3 py-1 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase">
              {salon.name}
            </div>
            <p className="text-sm leading-relaxed text-[var(--muted)]">
              {salon.description
                ? salon.description.replace(/<[^>]*>/g, '').substring(0, 120) + (salon.description.length > 120 ? '...' : '')
                : 'Experience premium beauty services. Book your appointment online with ease.'}
            </p>
            {socialItems.length > 0 && (
              <div className="mt-4 flex items-center gap-2">
                {socialItems.map((social) => (
                  <a
                    key={social.icon}
                    href={social.link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex h-9 w-9 items-center justify-center border-2 border-[var(--cream)] bg-[var(--sand)] text-[var(--terra)] hover:bg-[var(--cream)]"
                    aria-label={social.icon}
                  >
                    <i className={`fab fa-${social.icon}`} />
                  </a>
                ))}
              </div>
            )}
          </div>

          <div>
            <h3 className="mb-3 font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--ink)] uppercase">Quick Links</h3>
            <ul className="space-y-2">
              {navItems.map((item) => (
                <li key={item.path}>
                  <Link to={item.path} className="text-sm text-[var(--muted)] hover:text-[var(--terra)]">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="mb-3 font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--ink)] uppercase">Contact</h3>
            <ul className="space-y-2">
              {contactItems.map((item, index) => (
                <li key={index} className="text-sm text-[var(--muted)]">
                  <span className="font-semibold text-[var(--ink)]">{item.label}:</span> {item.text}
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="border-t-2 border-[var(--cream)] pt-6 text-center">
          <p className="text-xs text-[var(--muted)] md:text-sm">
            Copyright 2025 {salon.name}. All rights reserved. Powered by{' '}
            <a href="https://limach.lk" target="_blank" rel="noopener noreferrer" className="font-semibold text-[var(--terra)] hover:text-[var(--sienna)]">
              Limach Web Solution
            </a>
          </p>
        </div>
      </div>
    </footer>
  );
};

export default SalonFooter;
