import { useEffect } from 'react';
import { Link } from 'react-router-dom';

const MarqueeItem = ({ children }) => (
  <span className="flex items-center gap-8 px-10 text-[10px] font-semibold tracking-[0.22em] text-white/85 uppercase">
    {children}
    <span className="w-1 h-1 rounded-full bg-white/40" />
  </span>
);

const HeroSection = ({ salons, bookings, averageRating = 0, onScrollToSalons }) => {
  const salonsCount = typeof salons === 'number' ? salons : salons?.length || 0;
  const bookingsCount = typeof bookings === 'number' ? bookings : bookings?.length || 0;
  const average = typeof averageRating === 'number' ? averageRating : parseFloat(averageRating) || 0;

  useEffect(() => {
    const finePointer = window.matchMedia('(pointer:fine)').matches;
    if (!finePointer) return undefined;

    const dot = document.getElementById('serenCursorDot');
    const ring = document.getElementById('serenCursorRing');
    if (!dot || !ring) return undefined;

    let rx = 0;
    let ry = 0;

    const handleMove = (event) => {
      dot.style.left = `${event.clientX}px`;
      dot.style.top = `${event.clientY}px`;
      rx += (event.clientX - rx) * 0.12;
      ry += (event.clientY - ry) * 0.12;
      ring.style.left = `${rx}px`;
      ring.style.top = `${ry}px`;
    };

    const grow = () => {
      ring.style.width = '56px';
      ring.style.height = '56px';
      ring.style.opacity = '0.3';
    };

    const shrink = () => {
      ring.style.width = '36px';
      ring.style.height = '36px';
      ring.style.opacity = '0.5';
    };

    const hoverEls = document.querySelectorAll('button, a, input');
    hoverEls.forEach((el) => {
      el.addEventListener('mouseenter', grow);
      el.addEventListener('mouseleave', shrink);
    });

    document.addEventListener('mousemove', handleMove);

    return () => {
      document.removeEventListener('mousemove', handleMove);
      hoverEls.forEach((el) => {
        el.removeEventListener('mouseenter', grow);
        el.removeEventListener('mouseleave', shrink);
      });
    };
  }, []);

  return (
    <>
      <div id="serenCursorDot" className="hidden md:block seren-cursor-dot" />
      <div id="serenCursorRing" className="hidden md:block seren-cursor-ring" />

      <section className="relative flex items-end md:items-center w-screen min-h-screen overflow-hidden bg-[#1a0f0a] pt-24 pb-12 md:pt-32 md:pb-0">
        <video
          className="pointer-events-none absolute inset-0 z-0 w-full h-full object-cover object-[60%_25%] md:object-[center_20%] seren-hero-ken"
          src="/Girl_touching_her_hair_202605240819.mp4"
          autoPlay
          loop
          muted
          playsInline
        />

        {/* Layered overlays: dark base + warm brand tint + edge vignette for cinematic depth */}
        <div className="absolute inset-0 z-1 bg-[linear-gradient(115deg,rgba(26,15,10,0.82)_0%,rgba(26,15,10,0.55)_45%,rgba(26,15,10,0.15)_75%,transparent_100%)]" />
        <div className="absolute inset-0 z-1 bg-[radial-gradient(ellipse_at_30%_50%,rgba(196,98,45,0.18),transparent_60%)] mix-blend-overlay" />
        <div className="absolute inset-0 z-1 bg-[linear-gradient(to_bottom,rgba(26,15,10,0.55)_0%,transparent_30%,transparent_65%,rgba(26,15,10,0.75)_100%)]" />

        <div className="pointer-events-none absolute bottom-[-40px] right-[-20px] z-3 select-none font-[family-name:Unbounded] text-[clamp(14rem,28vw,26rem)] font-black leading-none text-transparent [-webkit-text-stroke:1px_rgba(255,225,200,0.08)]">
          S
        </div>
        <div className="pointer-events-none absolute right-12 top-1/2 z-10 hidden -translate-y-1/2 rotate-90 text-[10px] font-medium tracking-[0.33em] text-slate-200/80 uppercase xl:block">
          Premium Salon Booking — Sri Lanka
        </div>

        <div className="relative z-10 w-full max-w-[1800px] mx-auto px-6 md:px-16">
          <div className="items-center hidden gap-3 mb-10 md:flex md:gap-4 hero-animate-1">
            <span className="bg-(--terra) px-3 py-1 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.2em] text-slate-200/80 uppercase">
              New
            </span>
            <span className="w-10 h-px bg-white/40" />
            <span className="text-[11px] tracking-[0.2em] text-slate-200/80 uppercase">
              Premium Beauty Platform
            </span>
          </div>

          <h1 className="font-[family-name:Unbounded] text-[clamp(2.8rem,7.5vw,7.2rem)] font-extrabold leading-[0.9] tracking-[-2px] text-slate-200/50 hero-animate-2 [text-shadow:0_2px_30px_rgba(0,0,0,0.45)]">
            BOOK
            <br />
            <span className="text-transparent [-webkit-text-stroke:2px_rgba(255,255,255,0.55)]">YOUR</span>
            <br />
            <span className="font-[family-name:Libre_Baskerville] text-[0.85em] italic font-normal tracking-[-1px] text-[#f5d7b8]/80">
              absolute
            </span>{' '}
            <span className="text-[color-mix(in_srgb,var(--terra)_80%,transparent)]">BEST.</span>
          </h1>

          <div className="mt-8 md:mt-16 grid gap-8 md:border-t border-white/15 md:pt-10 lg:grid-cols-[1fr_auto_1fr] lg:items-end lg:gap-16">
            <p className="hidden md:block max-w-md text-sm leading-7 text-slate-200/80 md:text-[15px] hero-animate-3">
              Discover verified salons and beauty studios across Sri Lanka. Real reviews, fixed prices,
              instant booking crafted for the modern beauty lover.
            </p>

            <div className="hidden md:flex gap-6 border border-white/20 bg-white/6 px-6 py-5 backdrop-blur-md md:gap-10 md:px-8 hero-animate-4">
              <div className="text-center">
                <div className="font-[family-name:Unbounded] text-2xl font-bold leading-none text-slate-200/80">
                  {salonsCount}
                  <span className="ml-0.5 text-base text-(--terra)">+</span>
                </div>
                <div className="mt-1.5 text-[10px] tracking-[0.2em] text-slate-200/80 uppercase">Salons</div>
              </div>
              <div className="text-center">
                <div className="font-[family-name:Unbounded] text-2xl font-bold leading-none text-slate-200/80">
                  {bookingsCount}
                  <span className="ml-0.5 text-base text-(--terra)">+</span>
                </div>
                <div className="mt-1.5 text-[10px] tracking-[0.2em] text-slate-200/80 uppercase">Bookings</div>
              </div>
              <div className="text-center">
                <div className="font-[family-name:Unbounded] text-2xl font-bold leading-none text-slate-200/80">
                  {average.toFixed(1)}
                  <span className="ml-0.5 text-base text-(--terra)">★</span>
                </div>
                <div className="mt-1.5 text-[10px] tracking-[0.2em] text-slate-200/80 uppercase">Rating</div>
              </div>
            </div>

            <div className="flex flex-col items-start gap-3 lg:items-end hero-animate-5">
              <button
                onClick={onScrollToSalons}
                className="inline-flex items-center gap-3 border border-white/20 bg-white/6 backdrop-blur-md px-8 py-4 font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.2em] text-slate-200/80 uppercase transition-all hover:-translate-y-0.5 hover:bg-white/12 hover:border-white/30"
              >
                Explore Salons
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
                </svg>
              </button>
              <Link
                to="/deals#how-it-works"
                className="hidden md:inline-block text-xs font-medium tracking-[0.15em] text-slate-200/80 uppercase transition-all hover:text-slate-200"
              >
                How it works →
              </Link>
            </div>
          </div>
        </div>
      </section>

      <div className="flex items-center h-11 overflow-hidden bg-(--terra)">
        <div className="seren-marquee-track">
          <MarqueeItem>Premium Salons</MarqueeItem>
          <MarqueeItem>Verified Professionals</MarqueeItem>
          <MarqueeItem>Instant Booking</MarqueeItem>
          <MarqueeItem>{salonsCount}+ Salons</MarqueeItem>
          <MarqueeItem>Fixed Pricing</MarqueeItem>
          <MarqueeItem>WhatsApp Confirmed</MarqueeItem>
          <MarqueeItem>Premium Salons</MarqueeItem>
          <MarqueeItem>Verified Professionals</MarqueeItem>
          <MarqueeItem>Instant Booking</MarqueeItem>
          <MarqueeItem>{salonsCount}+ Salons</MarqueeItem>
          <MarqueeItem>Fixed Pricing</MarqueeItem>
          <MarqueeItem>WhatsApp Confirmed</MarqueeItem>
        </div>
      </div>
    </>
  );
};

export default HeroSection;
