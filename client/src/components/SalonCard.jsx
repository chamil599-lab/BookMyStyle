import { Link, useNavigate } from 'react-router-dom';
import { useState, useEffect } from 'react';
import api from '../utils/api';

const FALLBACK_IMAGE =
  'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=1469&q=80';

const StarRow = ({ rating }) => {
  const full = Math.floor(rating);
  const half = rating - full >= 0.5;
  return (
    <div className="flex items-center gap-0.5 text-[13px]">
      {[1, 2, 3, 4, 5].map((i) => (
        <span
          key={i}
          className={
            i <= full
              ? 'text-[var(--terra2)]'
              : i === full + 1 && half
              ? 'text-[var(--terra2)] opacity-50'
              : 'text-[var(--cream)]'
          }
        >
          ★
        </span>
      ))}
    </div>
  );
};

const SalonCard = ({ salon, onBookClick }) => {
  const navigate = useNavigate();
  const [averageRating, setAverageRating] = useState(0);
  const [totalReviews, setTotalReviews] = useState(0);
  const [, setLoading] = useState(true);

  useEffect(() => {
    fetchRatings();
  }, [salon.id]);

  const fetchRatings = async () => {
    try {
      const response = await api.get(`/salons/${salon.id}/ratings`);
      const data = response.data;
      if (data.ratings && Array.isArray(data.ratings)) {
        setAverageRating(data.average || 0);
        setTotalReviews(data.count || 0);
      } else if (Array.isArray(data) && data.length > 0) {
        const total = data.reduce((sum, r) => sum + r.rating, 0);
        setAverageRating(parseFloat((total / data.length).toFixed(1)));
        setTotalReviews(data.length);
      } else {
        setAverageRating(0);
        setTotalReviews(0);
      }
    } catch {
      setAverageRating(0);
      setTotalReviews(0);
    } finally {
      setLoading(false);
    }
  };

  const handleBookClick = (e) => {
    e.preventDefault();
    navigate(`/salon/${salon.id}?tab=booking`);
  };

  const getImageUrl = () => {
    if (salon.cover_thumb_url) return salon.cover_thumb_url;
    if (salon.cover_url) return salon.cover_url;
    if (salon.cover_image) {
      if (salon.cover_image.startsWith('http')) return salon.cover_image;
      if (salon.cover_image.startsWith('/')) return `https://bookmystyle.limach.lk${salon.cover_image}`;
      return `https://bookmystyle.limach.lk/storage/${salon.cover_image}`;
    }
    return FALLBACK_IMAGE;
  };

  const handleImageError = (e) => { e.target.src = FALLBACK_IMAGE; };

  const shownRating = averageRating > 0 ? averageRating : parseFloat(salon.rating || 0) || 0;
  const shownReviews = totalReviews > 0 ? totalReviews : parseInt(salon.review_count || 0, 10) || 0;
  const categoryLabel = salon.services?.[0]?.department?.name || 'Beauty & Wellness';

  return (
    <Link
      to={`/salon/${salon.id}`}
      className="group relative block overflow-hidden bg-[var(--white)] transition-all duration-400 md:border md:border-[var(--cream)] md:shadow-[0_2px_16px_rgba(30,23,16,0.07)] md:hover:-translate-y-1.5 md:hover:shadow-[0_12px_40px_rgba(30,23,16,0.14)]"
    >
      {/* ── MOBILE: compact horizontal card ── */}
      <div className="md:hidden flex gap-3 p-3 bg-white shadow-[0_2px_10px_rgba(30,23,16,0.06)]">
        <div className="relative shrink-0 w-24 h-24 overflow-hidden">
          <img
            src={getImageUrl()}
            alt={salon.name}
            className="w-full h-full object-cover"
            onError={handleImageError}
            loading="lazy"
          />
        </div>
        <div className="min-w-0 flex-1 flex flex-col justify-between py-0.5">
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0">
              <h3 className="text-base font-bold text-[var(--ink)] leading-tight truncate">
                {salon.name}
              </h3>
              <div className="mt-1 flex items-center gap-1.5 text-[12px]">
                <span className="text-[var(--terra2)]">★</span>
                {shownRating > 0 && (
                  <span className="font-semibold text-[var(--ink)]">{shownRating.toFixed(1)}</span>
                )}
                {shownReviews > 0 && (
                  <span className="text-[var(--muted)]">({shownReviews})</span>
                )}
                <span className="text-[var(--muted)]">·</span>
                <span className="text-[var(--terra)] font-medium truncate">{categoryLabel}</span>
              </div>
            </div>
          </div>
          {salon.location && (
            <div className="mt-1 flex items-center gap-1 text-[12px] text-[var(--muted)] truncate">
              <svg className="h-3 w-3 text-[var(--terra)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span className="truncate">{salon.location}</span>
            </div>
          )}
        </div>
      </div>

      {/* ── DESKTOP: original card ── */}
      <div className="hidden md:block">
      <div className="relative h-64 overflow-hidden">
        <img
          src={getImageUrl()}
          alt={salon.name}
          className="h-full w-full object-cover transition duration-700 group-hover:scale-108"
          onError={handleImageError}
          loading="lazy"
        />

        {/* Gradient for text legibility */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent" />

        {/* Category badge — top left */}
        <div className="absolute left-4 top-4 bg-[var(--terra)] px-2.5 py-1 font-[Unbounded] text-[9px] font-semibold tracking-[0.18em] text-white uppercase">
          {categoryLabel}
        </div>

        {/* Top Rated badge — top right */}
        {shownRating >= 4.5 && (
          <div className="absolute right-4 top-4 flex items-center gap-1 bg-black/40 px-2.5 py-1 font-[Unbounded] text-[9px] font-semibold tracking-[0.1em] text-[var(--terra2)] uppercase">
            <span>★</span> Top Rated
          </div>
        )}

        {/* Salon name + location over image */}
        <div className="absolute bottom-0 left-0 right-0 px-5 pb-5">
          <h3 className="font-[Libre_Baskerville] text-[1.35rem] font-bold leading-tight text-white">
            {salon.name}
          </h3>
          {salon.location && (
            <div className="mt-1.5 flex items-center gap-1.5 text-[11px] text-white/60">
              <svg className="h-3 w-3 text-[var(--terra2)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {salon.location}
            </div>
          )}
        </div>
      </div>

      {/* ── CARD BODY ── */}
      <div className="p-5">

        {/* Rating row — always visible */}
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <StarRow rating={shownRating} />
            {shownRating > 0 && (
              <span className="font-[Unbounded] text-xs font-bold text-[var(--ink)]">
                {shownRating.toFixed(1)}
              </span>
            )}
          </div>
          {shownReviews > 0 && (
            <span className="text-[11px] text-[var(--muted)]">{shownReviews} reviews</span>
          )}
        </div>

        {/* Description */}
        {salon.description && (
          <p className="mb-4 text-[12.5px] leading-relaxed text-[var(--muted)] line-clamp-2">
            {salon.description}
          </p>
        )}

        {/* Info row */}
        <div className="mb-5 flex items-center gap-4 text-[11px] text-[var(--muted)]">
          {salon.services?.length > 0 && (
            <span className="flex items-center gap-1.5">
              <span className="text-[var(--terra)]">✦</span>
              {salon.services.length} Services
            </span>
          )}
          {salon.open_time && salon.close_time && (
            <span className="flex items-center gap-1.5">
              <span className="text-[var(--terra)]">◷</span>
              {salon.open_time} – {salon.close_time}
            </span>
          )}
          <span className="flex items-center gap-1.5">
            <span className="text-[var(--terra)]">✓</span>
            Fixed Prices
          </span>
        </div>

        {/* Footer row */}
        <div className="flex items-center gap-3 border-t border-[var(--cream)] pt-4">
          <Link
            to={`/salon/${salon.id}`}
            className="flex-1 flex items-center justify-center gap-2 border border-[var(--terra)]/40 py-2.5 font-[Unbounded] text-[10px] font-semibold tracking-[0.15em] text-[var(--terra)] uppercase transition hover:bg-[var(--terra)] hover:text-white hover:border-[var(--terra)]"
          >
            View Salon
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
            </svg>
          </Link>
          <button
            onClick={handleBookClick}
            className="flex-1 bg-[var(--ink)] py-2.5 font-[Unbounded] text-[10px] font-semibold tracking-[0.15em] text-white uppercase transition hover:bg-[var(--terra)]"
          >
            Book Now
          </button>
        </div>
      </div>

      {/* Terra bottom accent on hover */}
      <div className="absolute bottom-0 left-0 h-[3px] w-0 bg-[var(--terra)] transition-all duration-500 group-hover:w-full" />
      </div>
    </Link>
  );
};

export default SalonCard;
