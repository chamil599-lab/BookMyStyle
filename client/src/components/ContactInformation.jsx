import { FaFacebook, FaInstagram, FaTiktok, FaWhatsapp } from 'react-icons/fa6';

const ContactRow = ({ icon, label, children }) => (
  <div className="flex items-start gap-4 px-6 py-5 border-b border-[var(--cream)] last:border-b-0 transition-colors hover:bg-[var(--sand)] group">
    <div className="shrink-0 flex h-10 w-10 items-center justify-center bg-[var(--terra)] text-white transition-colors group-hover:bg-[var(--sienna)]">
      {icon}
    </div>
    <div className="min-w-0 flex-1">
      <p className="mb-1 font-[Unbounded] text-[9px] font-semibold tracking-[0.28em] text-[var(--terra)] uppercase">
        {label}
      </p>
      <div className="text-[14px] font-medium text-[var(--ink)] leading-relaxed">
        {children}
      </div>
    </div>
  </div>
);

const ContactInformation = ({ salon }) => {
  const hasSocials = salon.facebook_page || salon.instagram_page || salon.tiktok_page;

  return (
    <div className="overflow-hidden border-2 border-[var(--cream)] bg-[var(--white)] shadow-[0_4px_24px_rgba(30,23,16,0.08)]">

      {/* Header */}
      <div className="bg-[var(--ink)] px-6 py-6">
        <div className="mb-2 font-[Unbounded] text-[9px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase">
          Get In Touch
        </div>
        <h3 className="font-[Libre_Baskerville] text-2xl italic text-white">
          Contact &amp; Hours
        </h3>
        <div className="mt-3 h-[2px] w-10 bg-[var(--terra)]" />
      </div>

      {/* Rows */}
      <div className="divide-y divide-[var(--cream)]">

        {/* Address */}
        {salon.location && (
          <ContactRow
            label="Address"
            icon={
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            }
          >
            {salon.location}
          </ContactRow>
        )}

        {/* Phone */}
        {salon.phone && (
          <ContactRow
            label="Phone"
            icon={
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            }
          >
            <div className="flex items-center gap-3 flex-wrap">
              <a href={`tel:${salon.phone}`} className="hover:text-[var(--terra)] transition-colors">
                {salon.phone}
              </a>
              <a
                href={`https://wa.me/${salon.phone.replace(/\D/g, '')}`}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 bg-[#25D366] px-3 py-1 text-[10px] font-[Unbounded] font-semibold tracking-[0.12em] text-white transition hover:opacity-90"
              >
                <FaWhatsapp className="w-3 h-3" />
                WhatsApp
              </a>
            </div>
          </ContactRow>
        )}

        {/* Email */}
        {salon.email && (
          <ContactRow
            label="Email"
            icon={
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            }
          >
            <a
              href={`mailto:${salon.email}`}
              className="break-all hover:text-[var(--terra)] transition-colors"
            >
              {salon.email}
            </a>
          </ContactRow>
        )}

        {/* Working Hours */}
        {(salon.open_time || salon.close_time) && (
          <ContactRow
            label="Working Hours"
            icon={
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            }
          >
            <div className="flex items-center gap-2">
              <span>{salon.open_time}</span>
              <span className="text-[var(--terra)] font-bold">–</span>
              <span>{salon.close_time}</span>
              <span className="ml-1 inline-flex items-center gap-1 bg-green-100 px-2 py-0.5 text-[10px] font-[Unbounded] font-semibold tracking-wide text-green-700">
                <span className="w-1.5 h-1.5 rounded-full bg-green-500" />
                Open
              </span>
            </div>
          </ContactRow>
        )}

      </div>

      {/* Social links */}
      {hasSocials && (
        <div className="border-t-2 border-[var(--cream)] bg-[var(--sand)] px-6 py-5">
          <p className="mb-4 font-[Unbounded] text-[9px] font-semibold tracking-[0.3em] text-[var(--muted)] uppercase">
            Follow Us
          </p>
          <div className="flex gap-2">
            {salon.facebook_page && (
              <a
                href={salon.facebook_page}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Facebook"
                className="flex flex-1 items-center justify-center gap-2 border-2 border-[var(--cream)] bg-[var(--white)] py-3 font-[Unbounded] text-[9px] font-semibold tracking-[0.15em] text-[var(--ink)] uppercase transition hover:bg-[#1877F2] hover:border-[#1877F2] hover:text-white"
              >
                <FaFacebook className="w-4 h-4 text-[1rem]" />
              </a>
            )}
            {salon.instagram_page && (
              <a
                href={salon.instagram_page}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Instagram"
                className="flex flex-1 items-center justify-center gap-2 border-2 border-[var(--cream)] bg-[var(--white)] py-3 font-[Unbounded] text-[9px] font-semibold tracking-[0.15em] text-[var(--ink)] uppercase transition hover:bg-[#E1306C] hover:border-[#E1306C] hover:text-white"
              >
                <FaInstagram className="w-4 h-4 text-[1rem]" />
              </a>
            )}
            {salon.tiktok_page && (
              <a
                href={salon.tiktok_page}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="TikTok"
                className="flex flex-1 items-center justify-center gap-2 border-2 border-[var(--cream)] bg-[var(--white)] py-3 font-[Unbounded] text-[9px] font-semibold tracking-[0.15em] text-[var(--ink)] uppercase transition hover:bg-[var(--ink)] hover:border-[var(--ink)] hover:text-white"
              >
                <FaTiktok className="w-4 h-4 text-[1rem]" />
              </a>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default ContactInformation;
