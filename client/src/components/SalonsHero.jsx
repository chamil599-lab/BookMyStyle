const BG_IMAGE =
  'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=1920&q=85';

const SalonsHero = ({ totalSalons = 0, averageRating = 0, searchTerm, onSearchChange, onScrollToSalons }) => {
  const rating = parseFloat(averageRating) || 0;

  return (
    <section className="relative w-full overflow-hidden flex items-center" style={{ minHeight: '88vh' }}>

      {/* Background image */}
      <div
        className="absolute inset-0 bg-center bg-cover"
        style={{ backgroundImage: `url('${BG_IMAGE}')` }}
      />

      {/* Overlays */}
      <div className="absolute inset-0 bg-[linear-gradient(115deg,rgba(15,10,5,0.93)_0%,rgba(15,10,5,0.70)_50%,rgba(139,58,30,0.30)_100%)]" />
      <div className="absolute inset-0 bg-linear-to-b from-black/20 via-transparent to-black/40" />

      {/* Watermark letter */}
      <div className="pointer-events-none absolute -bottom-10 right-0 select-none font-[family-name:Unbounded] text-[clamp(12rem,22vw,20rem)] font-black leading-none text-transparent [-webkit-text-stroke:1px_rgba(196,98,45,0.10)]">
        S
      </div>

      {/* Vertical side label */}
      <div className="pointer-events-none absolute right-8 top-1/2 z-10 hidden -translate-y-1/2 rotate-90 text-[9px] font-medium tracking-[0.33em] text-white/20 uppercase xl:block hero-animate-5">
        Premium Salons — Sri Lanka
      </div>

      <div className="relative z-10 w-full mx-auto max-w-[1300px] px-6 md:px-12 pt-32 pb-20">

        {/* Eyebrow */}
        <div className="mb-8 flex items-center gap-3 hero-animate-1">
          <span className="bg-(--terra) px-3 py-1 font-[family-name:Unbounded] text-[9px] font-semibold tracking-[0.2em] text-white uppercase">
            Sri Lanka
          </span>
          <span className="h-px w-8 bg-white/25" />
          <span className="font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.28em] text-white/45 uppercase">
            Browse · Discover · Book
          </span>
        </div>

        {/* Headline */}
        <h1 className="font-[family-name:Unbounded] text-[clamp(3rem,8vw,6.5rem)] font-extrabold leading-[0.9] tracking-[-2px] text-white hero-animate-2">
          FIND YOUR
          <br />
          <span className="font-[family-name:Libre_Baskerville] text-[0.78em] italic font-normal tracking-[-1px] text-(--blush)">
            perfect
          </span>
          <br />
          SALON.
        </h1>

        {/* Search bar */}
        <div className="mt-12 flex items-stretch max-w-xl hero-animate-3">
          <div className="flex flex-1 items-center gap-3 border border-white/20 bg-white/10 px-4 py-3.5">
            <svg className="h-4 w-4 text-white/50 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              placeholder="Salon name or location..."
              className="w-full border-none bg-transparent text-sm text-white placeholder:text-white/40 outline-hidden"
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
            />
          </div>
          <button
            onClick={onScrollToSalons}
            className="bg-(--terra) px-6 text-white transition-colors hover:bg-(--sienna) shrink-0 flex items-center justify-center"
          >
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
            </svg>
          </button>
        </div>

        {/* Stats */}
        <div className="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-white/10 pt-8 hero-animate-4">
          <div className="flex items-center gap-2.5">
            <span className="font-[family-name:Unbounded] text-2xl font-extrabold text-white leading-none">
              {totalSalons}<span className="text-base text-(--terra2)">+</span>
            </span>
            <span className="text-[11px] tracking-[0.18em] text-white/45 uppercase">Verified Salons</span>
          </div>

          <div className="h-4 w-px bg-white/15" />

          {rating > 0 && (
            <>
              <div className="flex items-center gap-2.5">
                <span className="font-[family-name:Unbounded] text-2xl font-extrabold text-white leading-none">
                  {rating.toFixed(1)}<span className="text-base text-(--terra2)">★</span>
                </span>
                <span className="text-[11px] tracking-[0.18em] text-white/45 uppercase">Avg Rating</span>
              </div>
              <div className="h-4 w-px bg-white/15" />
            </>
          )}

          <div className="flex items-center gap-2.5">
            <span className="font-[family-name:Unbounded] text-2xl font-extrabold text-white leading-none">Fixed</span>
            <span className="text-[11px] tracking-[0.18em] text-white/45 uppercase">Prices</span>
          </div>
        </div>
      </div>

      {/* Bottom terra line */}
      <div className="absolute bottom-0 left-0 right-0 h-[3px] bg-[linear-gradient(to_right,var(--terra),rgba(196,98,45,0.3)_60%,transparent)]" />
    </section>
  );
};

export default SalonsHero;
