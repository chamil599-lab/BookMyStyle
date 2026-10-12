import { useState, useEffect, useMemo } from 'react';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import api from '../utils/api';

import BookingForm from '../components/BookingForm';
import Gallery from '../components/Gallery';
import SalonFooter from '../components/SalonFooter';
import ContactInformation from '../components/ContactInformation';
import ReviewSection from '../components/ReviewSection';
import SalonHero from '../components/SalonHero';
import useScrollAnimation from '../hooks/useScrollAnimation';

const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=1469&q=80';

function getServiceCategoryLabel(service) {
  if (typeof service?.category_name === 'string' && service.category_name.trim()) return service.category_name.trim();
  if (typeof service?.category === 'string' && service.category.trim()) return service.category.trim();
  if (service?.category && typeof service.category === 'object') {
    if (typeof service.category.name === 'string' && service.category.name.trim()) return service.category.name.trim();
    if (typeof service.category.title === 'string' && service.category.title.trim()) return service.category.title.trim();
  }
  if (service?.department && typeof service.department === 'object') {
    if (typeof service.department.name === 'string' && service.department.name.trim()) return service.department.name.trim();
    if (typeof service.department.title === 'string' && service.department.title.trim()) return service.department.title.trim();
  }
  return 'General';
}

function normalizeServices(payload) {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload?.services)) return payload.services;
  return [];
}

const TABS = [
  { key: 'overview', label: 'Overview' },
  { key: 'services', label: 'Services' },
  { key: 'booking', label: 'Book Now' },
  { key: 'gallery', label: 'Gallery' },
  { key: 'reviews', label: 'Reviews' },
];

const MOBILE_TABS = [
  { key: 'booking', label: 'Booking', icon: 'event_available' },
  { key: 'about',   label: 'About',   icon: 'info' },
  { key: 'reviews', label: 'Reviews', icon: 'star' },
];

const FALLBACK_MARQUEE = ['Premium Services', 'Expert Stylists', 'Instant Booking', 'Top Rated', 'Fixed Pricing', 'WhatsApp Confirmed'];

const SalonDetailPage = () => {
  const scrollRef = useScrollAnimation();
  const { id } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [favorited, setFavorited] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [galleryOpen, setGalleryOpen] = useState(false);

  const [salon, setSalon] = useState(null);
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [mainImage, setMainImage] = useState(DEFAULT_IMAGE);
  const [images, setImages] = useState([]);
  const [timeSlots, setTimeSlots] = useState([]);
  const [activeTab, setActiveTab] = useState(searchParams.get('tab') === 'booking' ? 'booking' : 'overview');
  const [mobileTab, setMobileTab] = useState('booking');
  const [serviceFilter, setServiceFilter] = useState('all');

  const categories = useMemo(() => Array.from(new Set(services.map(getServiceCategoryLabel))), [services]);

  const filteredServices = useMemo(
    () => (serviceFilter === 'all' ? services : services.filter((s) => getServiceCategoryLabel(s) === serviceFilter)),
    [services, serviceFilter],
  );

  const updateImagesFromSalon = (salonData) => {
    setMainImage(salonData.cover_url || DEFAULT_IMAGE);
    const all = [];

    if (salonData.cover_url) {
      all.push({ type: 'cover', url: salonData.cover_url, thumb: salonData.cover_thumb_url || salonData.cover_url });
    }

    (salonData.gallery || []).forEach((img) => {
      all.push({
        type: 'gallery',
        url: img.preview || img.original,
        original: img.original,
        thumb: img.thumb,
        id: img.id,
      });
    });

    if (all.length === 0) all.push({ type: 'default', url: DEFAULT_IMAGE });
    setImages(all);
  };

  const fetchSalonDetails = () =>
    api
      .get(`/salons/${id}`)
      .then((res) => {
        setSalon(res.data);
        updateImagesFromSalon(res.data);
      })
      .catch((err) => console.error('Error refreshing salon:', err));

  useEffect(() => {
    setLoading(true);
    setError(null);
    setServiceFilter('all');
    setServices([]);

    const loadServices = async (salonData) => {
      const fromSalon = normalizeServices(salonData?.services);
      if (fromSalon.length > 0) {
        setServices(fromSalon);
        return;
      }

      try {
        const r1 = await api.get(`/salons/${id}/services`);
        const fromEndpoint = normalizeServices(r1?.data);
        if (fromEndpoint.length > 0) {
          setServices(fromEndpoint);
          return;
        }
      } catch (_) {}

      try {
        const r2 = await api.get(`/services/owner/${id}`);
        setServices(normalizeServices(r2?.data));
      } catch (_) {
        setServices([]);
      }
    };

    const generateTimeSlots = () => {
      const slots = [];
      for (let h = 9; h < 19; h++) {
        for (let m = 0; m < 60; m += 30) {
          const dh = h > 12 ? h - 12 : h;
          const ampm = h >= 12 ? 'PM' : 'AM';
          slots.push({
            display: `${dh}:${m === 0 ? '00' : m} ${ampm}`,
            value: `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`,
          });
        }
      }
      setTimeSlots(slots);
    };

    api
      .get(`/salons/${id}`)
      .then(async (res) => {
        const data = res.data;
        setSalon(data);
        updateImagesFromSalon(data);
        generateTimeSlots();
        await loadServices(data);
      })
      .catch((err) => {
        console.error('Error fetching salon:', err);
        setError('Salon not found or API error.');
      })
      .finally(() => setLoading(false));
  }, [id]);

  if (loading)
    return (
      <div className="min-h-screen flex items-center justify-center bg-(--sand) text-(--ink)">
        <div className="text-center">
          <div className="relative inline-block">
            <div className="animate-spin rounded-full h-20 w-20 border-4 border-(--cream)" />
            <div className="animate-spin rounded-full h-20 w-20 border-4 border-(--terra) border-t-transparent absolute inset-0" />
          </div>
          <p className="mt-6 text-(--muted) font-semibold text-lg">Loading salon details...</p>
        </div>
      </div>
    );

  if (error)
    return (
      <div className="min-h-screen flex items-center justify-center bg-(--sand) text-(--ink)">
        <div className="text-center bg-(--white) p-10 border-2 border-(--cream) max-w-md">
          <div className="w-20 h-20 bg-(--cream) flex items-center justify-center mx-auto mb-4">
            <svg className="w-10 h-10 text-(--terra)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p className="text-(--sienna) text-xl font-bold">{error}</p>
        </div>
      </div>
    );

  if (!salon)
    return (
      <div className="min-h-screen flex items-center justify-center bg-(--sand) text-(--ink)">
        <p className="text-(--muted) text-xl font-semibold">Salon not found</p>
      </div>
    );

  const rating = parseFloat(salon.rating || 0);
  const reviewCount = salon.review_count || salon.reviews_count || salon.reviews?.length || 0;
  const subtitle = salon.location || (services[0] ? getServiceCategoryLabel(services[0]) : '');
  const marqueeItems = services.length > 0 ? services.slice(0, 6).map((s) => s.title || s.name) : FALLBACK_MARQUEE;
  const marqueeDouble = [...marqueeItems, ...marqueeItems];

  const handleShare = async () => {
    setMobileMenuOpen(false);
    const shareData = { title: salon.name, text: `Check out ${salon.name} on BookMyStyle`, url: window.location.href };
    if (navigator.share) {
      try { await navigator.share(shareData); } catch (_) {}
    } else {
      try { await navigator.clipboard.writeText(window.location.href); alert('Link copied'); } catch (_) {}
    }
  };

  return (
    <div ref={scrollRef} className="min-h-screen bg-(--sand) text-(--ink)">
      <Helmet>
        <title>{`${salon.name} - Book Appointment | ${services.length} Services | BookMyStyle`}</title>
        <meta
          name="description"
          content={`${salon.description?.substring(0, 155)}... Book online at ${salon.name}. ${
            salon.rating ? `${parseFloat(salon.rating).toFixed(1)} star rating.` : ''
          } ${services.length} professional services available.`}
        />
        <meta
          name="keywords"
          content={`${salon.name}, ${salon.location || 'salon'}, ${services
            .slice(0, 5)
            .map((s) => s.title)
            .join(', ')}, beauty salon booking`}
        />
      </Helmet>

      {/* ── MOBILE LAYOUT ── */}
      {galleryOpen && (
        <div className="md:hidden fixed inset-0 z-80 bg-black overflow-y-auto">
          <button
            type="button"
            onClick={() => setGalleryOpen(false)}
            aria-label="Close gallery"
            className="fixed top-4 right-4 z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white/95 shadow-md"
          >
            <svg className="w-5 h-5 text-(--ink)" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <div className="pt-16 pb-10 px-3 space-y-3">
            {images.length === 0 ? (
              <p className="text-white/70 text-center py-20">No images yet.</p>
            ) : (
              images.map((img, i) => (
                <img
                  key={img.id || i}
                  src={img.url}
                  alt={`${salon.name} ${i + 1}`}
                  className="w-full rounded-lg object-cover"
                />
              ))
            )}
          </div>
        </div>
      )}

      <div className="md:hidden bg-white min-h-screen">
        {/* Cover image with floating buttons */}
        <div className="relative w-full h-[320px] overflow-hidden">
          <img src={mainImage} alt={salon.name} className="w-full h-full object-cover" />
          <button
            type="button"
            onClick={() => navigate(-1)}
            aria-label="Back"
            className="absolute top-4 left-4 z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white/95 shadow-md"
          >
            <svg className="w-5 h-5 text-(--ink)" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div className="absolute top-4 right-4 z-10">
            <button
              type="button"
              onClick={() => setMobileMenuOpen((v) => !v)}
              aria-label="Menu"
              className="flex items-center justify-center w-10 h-10 rounded-full bg-white/95 shadow-md"
            >
              <svg className="w-5 h-5 text-(--ink)" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                <circle cx="5" cy="12" r="1.5" fill="currentColor" />
                <circle cx="12" cy="12" r="1.5" fill="currentColor" />
                <circle cx="19" cy="12" r="1.5" fill="currentColor" />
              </svg>
            </button>
            {mobileMenuOpen && (
              <div className="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-(--cream) overflow-hidden">
                <button
                  type="button"
                  onClick={() => { setMobileMenuOpen(false); setGalleryOpen(true); }}
                  className="block w-full text-left px-4 py-3 text-sm text-(--ink) hover:bg-(--cream)/40"
                >
                  View Gallery
                </button>
                <button type="button" onClick={handleShare} className="block w-full text-left px-4 py-3 text-sm text-(--ink) hover:bg-(--cream)/40 border-t border-(--cream)">
                  Share
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Identity card */}
        <div className="relative -mt-6 rounded-t-3xl bg-white px-5 pt-5 pb-4">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <h1 className="text-xl font-bold text-(--ink) truncate">{salon.name}</h1>
              <div className="mt-1.5 flex items-center gap-2 text-sm">
                <svg className="w-4 h-4 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.366 2.446a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 00-1.176 0l-3.366 2.446c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.06 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" />
                </svg>
                <span className="font-semibold text-(--ink)">{rating.toFixed(1)}</span>
                {reviewCount > 0 && <span className="text-(--muted)">({reviewCount})</span>}
                {subtitle && (
                  <>
                    <span className="text-(--muted)">·</span>
                    <span className="text-(--terra) font-medium truncate">{subtitle}</span>
                  </>
                )}
              </div>
            </div>
            <button
              type="button"
              onClick={() => setFavorited((v) => !v)}
              aria-label="Favorite"
              className="shrink-0 flex items-center justify-center w-10 h-10"
            >
              <svg className={`w-6 h-6 transition-colors ${favorited ? 'text-(--terra)' : 'text-(--muted)'}`} fill={favorited ? 'currentColor' : 'none'} viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z" />
              </svg>
            </button>
          </div>

          {/* Pill tabs */}
          <div className="mt-5 flex gap-2">
            {MOBILE_TABS.map((tab) => (
              <button
                key={tab.key}
                type="button"
                onClick={() => setMobileTab(tab.key)}
                className={`flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 text-sm font-medium transition-all border ${
                  mobileTab === tab.key
                    ? 'bg-(--terra) border-(--terra) text-white shadow-[0_4px_12px_rgba(196,98,45,0.3)]'
                    : 'bg-white border-(--cream) text-(--ink)'
                }`}
              >
                <span className="material-symbols-outlined text-base">{tab.icon}</span>
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        {/* Mobile tab content */}
        <div className="px-5 pb-32">
          {mobileTab === 'booking' && (
            services.length > 0
              ? <BookingForm salon={salon} services={services} timeSlots={timeSlots} />
              : <p className="text-(--muted) text-center py-10">No services available for booking yet.</p>
          )}

          {mobileTab === 'about' && (
            <div className="space-y-6 pt-2">
              <div>
                <h3 className="font-[family-name:Libre_Baskerville] text-xl italic text-(--ink) mb-2">About</h3>
                <p className="text-sm text-(--muted) leading-relaxed">{salon.description || 'No description available.'}</p>
              </div>
              <ContactInformation salon={salon} />
              {services.length > 0 && (
                <div>
                  <h3 className="font-[family-name:Libre_Baskerville] text-xl italic text-(--ink) mb-3">Services</h3>
                  <div className="space-y-2">
                    {services.map((service) => (
                      <div key={service.id} className="flex items-start justify-between gap-3 p-4 rounded-xl bg-(--cream)/40">
                        <div className="min-w-0">
                          <div className="text-[10px] font-semibold tracking-widest text-(--terra) uppercase mb-0.5">
                            {getServiceCategoryLabel(service)}
                          </div>
                          <h4 className="text-sm font-bold text-(--ink) truncate">{service.title || service.name}</h4>
                          <div className="text-xs text-(--muted) mt-0.5">{service.duration} mins</div>
                        </div>
                        {salon.show_price && (
                          <span className="text-sm font-black text-(--sienna) shrink-0">Rs. {service.price}</span>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {mobileTab === 'reviews' && (
            <div className="pt-2">
              <ReviewSection salonId={salon.id} onSubmitted={fetchSalonDetails} />
            </div>
          )}
        </div>
      </div>

      {/* ── DESKTOP LAYOUT ── */}
      <div className="hidden md:block">
      {/* ── HERO ── */}
      <SalonHero
        salon={salon}
        services={services}
        images={images}
        mainImage={mainImage}
        onSetMainImage={setMainImage}
        onBookNow={() => setActiveTab('booking')}
        onViewGallery={() => setActiveTab('gallery')}
      />

      {/* ── MARQUEE BAR ── */}
      <div className="flex items-center h-11 overflow-hidden bg-(--terra)">
        <div className="seren-marquee-track">
          {marqueeDouble.map((label, i) => (
            <span
              key={i}
              className="flex items-center gap-8 px-10 text-[10px] font-semibold tracking-[0.22em] text-white/85 uppercase"
            >
              {label}
              <span className="w-1 h-1 rounded-full bg-white/40" />
            </span>
          ))}
        </div>
      </div>

      {/* ── MAIN CONTENT ── */}
      <section className="relative px-6 py-20 overflow-hidden md:px-12">
        {/* Ghost letter decoration */}
        <div className="pointer-events-none absolute -top-8 right-4 select-none font-[family-name:Unbounded] text-[18rem] font-black leading-none text-transparent [-webkit-text-stroke:1px_rgba(196,98,45,0.07)] md:text-[22rem]">
          B
        </div>

        <div className="relative z-10 mx-auto max-w-[1300px]">

          {/* Section header */}
          <div className="grid items-end gap-10 mb-14 lg:grid-cols-2">
            <div className="scroll-animate anim-fade-in-left">
              <div className="mb-4 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                <span className="h-0.5 w-5 bg-(--terra)" />
                Salon Profile
              </div>
              <h2 className="font-[family-name:Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-(--ink) md:text-6xl">
                Discover
                <br />
                <em className="font-[family-name:Libre_Baskerville] text-(--terra)">{salon.name}</em>
              </h2>
            </div>
            <div className="scroll-animate anim-fade-in-right" style={{ animationDelay: '120ms' }}>
              <p className="mb-4 max-w-md text-sm leading-7 text-(--muted)">
                {salon.description
                  ? salon.description.substring(0, 200) + (salon.description.length > 200 ? '...' : '')
                  : 'A premium beauty destination offering exceptional services and a refined experience.'}
              </p>
              {salon.description && salon.description.length > 200 && (
                <button
                  onClick={() => setActiveTab('overview')}
                  className="inline-flex items-center gap-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.2em] text-(--terra) uppercase"
                >
                  Read More →
                </button>
              )}
            </div>
          </div>

          {/* Tabs – styled like homepage sort buttons */}
          <div
            className="mb-0 flex gap-2 overflow-x-auto border-b-2 border-(--cream) scroll-animate anim-fade-in-up"
            style={{ animationDelay: '80ms' }}
          >
            {TABS.map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className={`shrink-0 px-5 py-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase transition border-b-2 -mb-0.5 ${
                  activeTab === tab.key
                    ? 'border-(--terra) text-(--terra)'
                    : 'border-transparent text-(--muted) hover:text-(--ink)'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Service category filter bar – homepage sort bar style */}
          {(activeTab === 'services' || activeTab === 'overview') && categories.length > 0 && (
            <div
              className="mb-8 flex flex-col gap-4 border-b-2 border-(--cream) pb-5 pt-5 sm:flex-row sm:items-center sm:justify-between scroll-animate anim-fade-in-up"
              style={{ animationDelay: '120ms' }}
            >
              <span className="text-xs uppercase tracking-[0.2em] text-(--muted)">Filter by category</span>
              <div className="flex flex-wrap gap-2">
                <button
                  onClick={() => setServiceFilter('all')}
                  className={`px-4 py-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase transition ${
                    serviceFilter === 'all'
                      ? 'bg-(--terra) text-white'
                      : 'bg-(--cream) text-(--muted) hover:text-(--terra)'
                  }`}
                >
                  All ({services.length})
                </button>
                {categories.map((cat) => (
                  <button
                    key={cat}
                    onClick={() => setServiceFilter(cat)}
                    className={`px-4 py-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase transition ${
                      serviceFilter === cat
                        ? 'bg-(--terra) text-white'
                        : 'bg-(--cream) text-(--muted) hover:text-(--terra)'
                    }`}
                  >
                    {cat}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Two-column layout: content + sidebar */}
          <div className="flex flex-col gap-10 pt-8 lg:flex-row lg:items-start">
            {/* Main content column */}
            <div className="flex-1 min-w-0 space-y-6">

              {/* Overview tab */}
              {activeTab === 'overview' && (
                <div className="bg-(--white) border-2 border-(--cream) p-8 scroll-animate anim-fade-in-up">
                  <div className="mb-4 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                    <span className="h-0.5 w-5 bg-(--terra)" />
                    About
                  </div>
                  <h3 className="mb-4 font-[family-name:Libre_Baskerville] text-2xl italic text-(--ink)">
                    {salon.name}
                  </h3>
                  <p className="text-(--muted) leading-relaxed text-sm">
                    {salon.description || 'No description available.'}
                  </p>
                </div>
              )}

              {/* Services section – shown on both overview and services tabs */}
              {(activeTab === 'services' || activeTab === 'overview') && (
                <div className="bg-(--white) border-2 border-(--cream) p-8 scroll-animate anim-fade-in-up">
                  <div className="flex items-end justify-between mb-6">
                    <div>
                      <div className="mb-2 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                        <span className="h-0.5 w-5 bg-(--terra)" />
                        Services
                      </div>
                      <h3 className="font-[family-name:Libre_Baskerville] text-2xl italic text-(--ink)">
                        Our Offerings
                      </h3>
                    </div>
                    <div className="font-[family-name:Unbounded] text-4xl font-black text-(--terra) leading-none">
                      {filteredServices.length}
                    </div>
                  </div>

                  {services.length === 0 ? (
                    <p className="text-(--muted) text-center py-10">No services available yet.</p>
                  ) : filteredServices.length === 0 ? (
                    <div className="text-center py-10 text-(--muted)">
                      <p>No services in this category.</p>
                      <button
                        onClick={() => setServiceFilter('all')}
                        className="mt-2 text-(--sienna) text-sm font-semibold hover:underline"
                      >
                        Show all services
                      </button>
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      {filteredServices.map((service, index) => (
                        <div
                          key={service.id}
                          className="border-l-[3px] border-(--cream) bg-(--sand) p-4 transition hover:border-(--terra) hover:bg-(--white) scroll-animate anim-slide-in-stagger"
                          style={{ animationDelay: `${Math.min(index, 11) * 70}ms` }}
                        >
                          <div className="mb-1 font-[family-name:Unbounded] text-[9px] font-semibold tracking-[0.25em] text-(--terra) uppercase">
                            {getServiceCategoryLabel(service)}
                          </div>
                          <div className="flex items-start justify-between gap-3 mb-2">
                            <h4 className="text-[15px] font-bold text-(--ink)">{service.title || service.name}</h4>
                            {salon.show_price && (
                              <span className="text-[15px] font-black text-(--sienna) shrink-0">Rs. {service.price}</span>
                            )}
                          </div>
                          {service.description && (
                            <p
                              className="text-sm text-(--muted) leading-relaxed mb-3 line-clamp-2"
                              dangerouslySetInnerHTML={{ __html: service.description.replace(/<[^>]*>/g, '') }}
                            />
                          )}
                          <div className="mt-3 flex items-center justify-between text-sm text-(--muted) border-t border-black/5 pt-2">
                            <span>{service.duration} mins</span>
                            <button
                              onClick={() => setActiveTab('booking')}
                              className="bg-(--ink) px-3 py-1 font-[family-name:Unbounded] text-[9px] font-bold tracking-[0.14em] text-white uppercase transition-colors hover:bg-(--terra)"
                            >
                              Book
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {/* Booking tab */}
              {activeTab === 'booking' && (
                <div className="bg-(--white) border-2 border-(--cream) p-8 scroll-animate anim-fade-in-up">
                  <div className="mb-2 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                    <span className="h-0.5 w-5 bg-(--terra)" />
                    Reservations
                  </div>
                  <h3 className="mb-6 font-[family-name:Libre_Baskerville] text-2xl italic text-(--ink)">
                    Book Your Appointment
                  </h3>
                  {services.length > 0 ? (
                    <BookingForm salon={salon} services={services} timeSlots={timeSlots} />
                  ) : (
                    <p className="text-(--muted) text-center py-8">No services available for booking yet.</p>
                  )}
                </div>
              )}

              {/* Gallery tab */}
              {activeTab === 'gallery' && (
                <div className="bg-(--white) border-2 border-(--cream) p-8 scroll-animate anim-fade-in-up">
                  <div className="mb-2 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                    <span className="h-0.5 w-5 bg-(--terra)" />
                    Visual Gallery
                  </div>
                  <h3 className="mb-6 font-[family-name:Libre_Baskerville] text-2xl italic text-(--ink)">
                    Our Space
                  </h3>
                  <Gallery images={images} salonName={salon.name} onImageClick={setMainImage} />
                </div>
              )}

              {/* Reviews tab */}
              {activeTab === 'reviews' && <ReviewSection salonId={salon.id} onSubmitted={fetchSalonDetails} />}
            </div>

            {/* ── SIDEBAR ── */}
            <aside
              className="w-full lg:sticky lg:top-24 lg:w-[270px] scroll-animate anim-fade-in-right"
              style={{ animationDelay: '160ms' }}
            >
              <div className="space-y-4">
                {/* Book CTA card – dark header style matching homepage sidebar */}
                {activeTab !== 'booking' && services.length > 0 && (
                  <div className="overflow-hidden border-2 border-(--cream) bg-(--white) scroll-animate anim-scale-in">
                    <div className="bg-(--ink) p-6">
                      <div className="mb-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra2) uppercase">
                        Ready to Book?
                      </div>
                      <h3 className="font-[family-name:Libre_Baskerville] text-2xl italic text-white">
                        Experience the Best
                      </h3>
                    </div>
                    <div className="p-6">
                      <p className="mb-5 text-sm text-(--muted) leading-relaxed">
                        Book your next beauty appointment and experience premium care.
                      </p>
                      <div className="flex flex-col gap-2">
                        <button
                          onClick={() => setActiveTab('booking')}
                          className="w-full bg-(--terra) text-white py-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase hover:bg-(--sienna) transition"
                        >
                          Book Now →
                        </button>
                        <button
                          onClick={() => setActiveTab('gallery')}
                          className="w-full border-2 border-(--cream) bg-(--sand) py-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] text-(--muted) uppercase hover:border-(--terra) hover:text-(--terra) transition"
                        >
                          View Gallery
                        </button>
                      </div>

                      <div className="my-5 h-0.5 bg-(--cream)" />

                      {/* Quick stats in sidebar – matching homepage counter style */}
                      <div className="mb-5 flex items-center gap-4 border-l-4 border-(--terra) bg-(--cream) px-4 py-4">
                        <div className="font-[family-name:Unbounded] text-3xl font-black text-(--terra)">
                          {services.length}
                        </div>
                        <div>
                          <div className="text-sm text-(--muted)">Services available</div>
                          {rating > 0 && (
                            <div className="text-sm text-(--muted)">{rating.toFixed(1)} ★ rated</div>
                          )}
                        </div>
                      </div>

                      <div className="my-5 h-0.5 bg-(--cream)" />

                      <div className="mb-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--muted) uppercase">
                        Why Book Here
                      </div>
                      <div className="space-y-2 text-sm text-(--muted)">
                        <div>→ Instant confirmation</div>
                        <div>→ {salon.show_price ? 'Transparent pricing' : 'Pricing on request'}</div>
                        <div>→ Verified professionals</div>
                      </div>
                    </div>
                  </div>
                )}

                <ContactInformation salon={salon} />
              </div>
            </aside>
          </div>
        </div>
      </section>

      </div>

      <div className="hidden md:block">
        <SalonFooter salon={salon} />
      </div>
    </div>
  );
};

export default SalonDetailPage;
