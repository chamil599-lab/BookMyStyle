import FavoriteButton from './FavoriteButton';

const StarRating = ({ rating }) => {
  const full = Math.floor(rating);
  const half = rating - full >= 0.5;
  return (
    <div className="flex items-center gap-0.5 text-[15px]">
      {[1, 2, 3, 4, 5].map((i) => (
        <span
          key={i}
          className={
            i <= full
              ? 'text-(--terra2)'
              : i === full + 1 && half
              ? 'text-(--terra2) opacity-50'
              : 'text-white/20'
          }
        >
          ★
        </span>
      ))}
    </div>
  );
};

const SalonHero = ({
  salon,
  services,
  images,
  mainImage,
  onSetMainImage,
  onBookNow,
  onViewGallery,
}) => {
  const rating = parseFloat(salon?.rating || 0);
  const categoryLabel =
    salon?.services?.[0]?.department?.name ||
    salon?.services?.[0]?.category_name ||
    'Beauty & Wellness';

  return (
    <section className="relative w-full overflow-hidden" style={{ minHeight: '100vh' }}>

      {/* ── FULL-BLEED BACKGROUND ── */}
      <div
        className="absolute inset-0 bg-center bg-cover transition-all duration-700"
        style={{ backgroundImage: `url('${mainImage}')` }}
      />

      {/* Gradient layers */}
      <div className="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(0,0,0,0.55)_0%,transparent_35%,transparent_40%,rgba(0,0,0,0.75)_70%,rgba(0,0,0,0.92)_100%)]" />
      <div className="absolute inset-0 bg-[linear-gradient(to_right,rgba(0,0,0,0.18)_0%,transparent_50%)]" />

      {/* ── TOP BAR ── */}
      <div className="absolute top-0 left-0 right-0 z-20 flex items-center justify-between px-8 md:px-14 pt-28 lg:pt-32">
        <div className="flex items-center gap-3 hero-animate-1">
          <span className="bg-(--terra) px-3 py-1 font-[family-name:Unbounded] text-[9px] font-semibold tracking-[0.2em] text-white uppercase">
            {categoryLabel}
          </span>
          <span className="h-px w-6 bg-white/20" />
          <span className="text-[9px] tracking-[0.2em] text-white/40 uppercase">Book My Style</span>
        </div>

        {images.length > 0 && (
          <div className="hidden lg:flex items-center gap-2 bg-black/30 backdrop-blur-xs px-3 py-1.5 border border-white/10 hero-animate-2">
            <svg className="w-3 h-3 text-white/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span className="font-[family-name:Unbounded] text-[9px] font-semibold text-white/55 tracking-widest">
              {images.length} Photos
            </span>
          </div>
        )}
      </div>

      {/* ── VERTICAL SIDE LABEL ── */}
      <div className="pointer-events-none absolute right-8 top-1/2 z-10 hidden -translate-y-1/2 rotate-90 text-[9px] font-medium tracking-[0.33em] text-white/15 uppercase xl:block">
        Premium Salon — Book My Style
      </div>

      {/* ── CENTRE CONTENT: salon name & meta ── */}
      <div className="absolute inset-x-0 z-20 flex flex-col items-center text-center px-6"
        style={{ bottom: 'calc(160px + env(safe-area-inset-bottom, 0px))' }}>

        <p className="font-[family-name:Libre_Baskerville] text-sm italic text-(--blush) mb-4 tracking-wide hero-animate-2">
          Premium Beauty Salon
        </p>

        <h1 className="font-[family-name:Unbounded] text-[clamp(2.4rem,7vw,6.5rem)] font-extrabold leading-[0.92] tracking-[-2px] text-white drop-shadow-[0_2px_24px_rgba(0,0,0,0.6)] hero-animate-3">
          {salon.name}
        </h1>

        <div className="flex flex-wrap items-center justify-center gap-4 mt-6 hero-animate-4">
          {salon.location && (
            <div className="flex items-center gap-1.5 text-[12px] text-white/65">
              <svg className="w-3.5 h-3.5 text-(--terra2) shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{salon.location}</span>
            </div>
          )}

          {rating > 0 && (
            <>
              <span className="w-px h-3 bg-white/20" />
              <div className="flex items-center gap-2">
                <StarRating rating={rating} />
                <span className="font-[family-name:Unbounded] text-sm font-bold text-white">{rating.toFixed(1)}</span>
                <span className="text-[11px] text-white/35">/ 5.0</span>
              </div>
            </>
          )}
        </div>

        {salon.description && (
          <p className="mt-5 max-w-xl text-[13px] leading-relaxed text-white/50 line-clamp-2 hidden md:block hero-animate-5">
            {salon.description}
          </p>
        )}
      </div>

      {/* ── THUMBNAIL STRIP ── */}
      {images.length > 1 && (
        <div
          className="absolute left-0 right-0 z-20 px-6 md:px-14 pb-3 flex gap-2 overflow-x-auto"
          style={{ bottom: 'calc(100px + env(safe-area-inset-bottom, 0px))' }}
        >
          {images.slice(0, 8).map((img, i) => (
            <button
              key={img.id || i}
              onClick={() => onSetMainImage(img.url)}
              className={`shrink-0 overflow-hidden border-2 transition-all duration-200 ${
                mainImage === img.url
                  ? 'border-(--terra) scale-[1.06] shadow-[0_4px_16px_rgba(196,98,45,0.45)]'
                  : 'border-white/20 opacity-55 hover:opacity-90 hover:border-white/50'
              }`}
            >
              <img
                src={img.thumb || img.url}
                alt="Salon preview"
                className="h-12 w-[64px] md:h-14 md:w-[76px] object-cover"
              />
            </button>
          ))}
          {images.length > 8 && (
            <button
              onClick={onViewGallery}
              className="shrink-0 flex items-center justify-center h-12 w-[64px] md:h-14 md:w-[76px] border-2 border-white/20 bg-black/40 backdrop-blur-xs font-[family-name:Unbounded] text-[9px] font-semibold tracking-widest text-white/60 uppercase hover:border-white/50 hover:bg-black/60 transition"
            >
              +{images.length - 8}
            </button>
          )}
        </div>
      )}

      {/* ── BOTTOM INFO RIBBON ── */}
      <div className="absolute bottom-0 left-0 right-0 z-20 border-t border-white/10 bg-black/45 backdrop-blur-md">
        <div className="mx-auto max-w-[1300px] px-6 md:px-14 py-5 flex flex-col md:flex-row items-start md:items-center gap-5 md:gap-0">

          {/* Stats */}
          <div className="flex items-center gap-6 md:gap-8 shrink-0">
            <div className="text-center">
              <div className="font-[family-name:Unbounded] text-xl font-bold text-white leading-none">
                {services.length}
                <span className="text-sm text-(--terra2) ml-0.5">+</span>
              </div>
              <div className="text-[9px] tracking-[0.2em] text-white/35 uppercase mt-1">Services</div>
            </div>

            <div className="w-px h-7 bg-white/8" />

            <div className="text-center">
              <div className="font-[family-name:Unbounded] text-xl font-bold text-white leading-none">
                {images.length}
                <span className="text-sm text-(--terra2) ml-0.5">+</span>
              </div>
              <div className="text-[9px] tracking-[0.2em] text-white/35 uppercase mt-1">Photos</div>
            </div>

            {salon.open_time && salon.close_time && (
              <>
                <div className="w-px h-7 bg-white/8" />
                <div className="text-center">
                  <div className="font-[family-name:Unbounded] text-[11px] font-bold text-white leading-snug tracking-wide">
                    {salon.open_time} – {salon.close_time}
                  </div>
                  <div className="text-[9px] tracking-[0.2em] text-white/35 uppercase mt-1">Hours</div>
                </div>
              </>
            )}
          </div>

          {/* Spacer */}
          <div className="flex-1" />

          {/* CTAs */}
          <div className="flex items-center gap-4 shrink-0">
            <FavoriteButton salonId={salon.id} salonName={salon.name} />

            <button
              onClick={onViewGallery}
              className="text-[11px] font-medium tracking-[0.15em] text-white/55 uppercase transition-all hover:text-white/90"
            >
              Gallery →
            </button>

            <button
              onClick={onBookNow}
              className="inline-flex items-center gap-2.5 bg-(--terra) px-7 py-3.5 font-[family-name:Unbounded] text-[10px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:-translate-y-0.5 hover:bg-(--sienna)"
            >
              Book Appointment
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

    </section>
  );
};

export default SalonHero;
