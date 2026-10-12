import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useQuery } from '@tanstack/react-query';
import api, { asArray, asObject } from '../utils/api';
import SalonCard from '../components/SalonCard';
import Footer from '../components/Footer';
import HeroSection from '../components/HeroSection';
import useScrollAnimation from '../hooks/useScrollAnimation';

const FILTER_ICONS = {
  All:    'apps',
  Gents:  'face',
  Ladies: 'face_3',
  Kids:   'child_care',
  Groom:  'man',
  Bride:  'woman',
};

const Homepage = () => {
  const scrollRef = useScrollAnimation();
  const [activeFilter, setActiveFilter] = useState('All');
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('rating');

  const { data: salons = [], isLoading: loading } = useQuery({
    queryKey: ['salons'],
    queryFn: async () => {
      const response = await api.get('/salons');
      return asArray(response.data);
    },
  });

  const { data: departments = [] } = useQuery({
    queryKey: ['departments'],
    queryFn: async () => {
      const response = await api.get('/departments');
      return asArray(response.data);
    },
  });

  const { data: stats = { totalSalons: 0, approvedBookings: 0, averageRating: 0 } } = useQuery({
    queryKey: ['stats'],
    queryFn: async () => {
      const response = await api.get('/stats');
      return asObject(response.data, { totalSalons: 0, approvedBookings: 0, averageRating: 0 });
    },
  });

  const handleSearch = (event) => {
    event.preventDefault();
  };

  const scrollToSalons = () => {
    const salonsSection = document.getElementById('salons-section');
    if (salonsSection) salonsSection.scrollIntoView({ behavior: 'smooth' });
  };

  const filters = ['All', ...departments.map((dept) => dept.name)];

  const getSalonRating = (salon) => {
    if (!salon) return 0;
    const rating = salon.rating || salon.average_rating || salon.avg_rating || 0;
    const numericRating = parseFloat(rating);
    return Number.isNaN(numericRating) ? 0 : Math.min(Math.max(numericRating, 0), 5);
  };

  const getSalonReviewCount = (salon) => {
    if (!salon) return 0;
    const count = salon.review_count || salon.total_reviews || salon.ratings_count || 0;
    const parsedCount = parseInt(count, 10);
    return Number.isNaN(parsedCount) ? 0 : parsedCount;
  };

  const filteredSalons = salons.filter((salon) => {
    if (!salon) return false;

    const matchesFilter =
      activeFilter === 'All' ||
      (salon.services &&
        Array.isArray(salon.services) &&
        salon.services.some((service) => service?.department?.name === activeFilter));

    const q = searchTerm.toLowerCase();
    const matchesSearch =
      searchTerm === '' ||
      salon.name?.toLowerCase().includes(q) ||
      salon.location?.toLowerCase().includes(q) ||
      salon.description?.toLowerCase().includes(q);

    return matchesFilter && matchesSearch;
  });

  const sortedAndFilteredSalons = [...filteredSalons].sort((a, b) => {
    switch (sortBy) {
      case 'rating': {
        return getSalonRating(b) - getSalonRating(a);
      }
      case 'name': {
        return (a.name || '').localeCompare(b.name || '');
      }
      case 'reviews': {
        return getSalonReviewCount(b) - getSalonReviewCount(a);
      }
      case 'newest': {
        const dateA = new Date(a.created_at || '1970-01-01');
        const dateB = new Date(b.created_at || '1970-01-01');
        return dateB - dateA;
      }
      default:
        return 0;
    }
  });

  const getFilterCount = (filterName) => {
    if (filterName === 'All') return salons.length;
    return salons.filter(
      (salon) =>
        salon.services &&
        Array.isArray(salon.services) &&
        salon.services.some((service) => service?.department?.name === filterName),
    ).length;
  };

  return (
    <div ref={scrollRef} className="min-h-screen bg-(--sand) text-(--ink)">
      <Helmet>
        <title>{`BookMyStyle - Book Top Rated Beauty Salons & Spas Online | ${stats.totalSalons}+ Salons`}</title>
        <meta
          name="description"
          content={`Book appointments at ${stats.totalSalons}+ verified beauty salons and spas. ${stats.averageRating} average rating. Browse trending salons, exclusive deals, and popular services near you.`}
        />
      </Helmet>

      <HeroSection
        salons={stats.totalSalons || 0}
        bookings={stats.approvedBookings || 0}
        averageRating={stats.averageRating || 0}
        onScrollToSalons={scrollToSalons}
      />

      <section id="salons-section" className="relative overflow-hidden px-4 py-8 md:px-12 md:py-24">
        <div className="hidden md:block pointer-events-none absolute -top-8 right-4 select-none font-[family-name:Unbounded] text-[18rem] font-black leading-none text-transparent [-webkit-text-stroke:1px_rgba(196,98,45,0.07)] md:text-[22rem]">
          S
        </div>

        {/* Mobile-only search + category chips */}
        <div className="md:hidden mb-5">
          <form onSubmit={handleSearch} className="mb-4">
            <div className="flex items-center gap-2 bg-white/40 backdrop-blur-md border border-(--ink)/10 px-4 py-3 shadow-[0_2px_10px_rgba(30,23,16,0.04)] focus-within:border-(--terra)/40 focus-within:bg-white/60 transition">
              <svg className="h-4 w-4 text-(--muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search salons or locations"
                className="w-full bg-transparent text-sm text-(--ink) placeholder:text-(--muted) outline-hidden"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </form>
          <div className="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-none [&::-webkit-scrollbar]:hidden">
            {filters.map((filter) => (
              <button
                key={filter}
                type="button"
                onClick={() => setActiveFilter(filter)}
                className={`shrink-0 min-w-22 inline-flex flex-col items-center justify-center gap-0.5 px-4 py-2 text-sm font-medium transition-all border ${
                  activeFilter === filter
                    ? 'bg-(--terra) border-(--terra) text-white shadow-[0_4px_12px_rgba(196,98,45,0.3)]'
                    : 'bg-white border-(--cream) text-(--ink)'
                }`}
              >
                <span className="material-symbols-outlined text-base">{FILTER_ICONS[filter] ?? 'category'}</span>
                <span>{filter}</span>
              </button>
            ))}
          </div>
        </div>

        <div className="relative z-10 mx-auto max-w-[1300px]">
          <div className="hidden md:grid mb-16 items-end gap-10 lg:grid-cols-2">
            <div className="scroll-animate anim-fade-in-left">
              <div className="mb-4 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                <span className="h-0.5 w-5 bg-(--terra)" />
                Our Collection
              </div>
              <h2 className="font-[family-name:Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-(--ink) md:text-6xl">
                Explore
                <br />
                <em className="font-[family-name:Libre_Baskerville] text-(--terra)">Our Salons</em>
              </h2>
            </div>
            <div className="scroll-animate anim-fade-in-right" style={{ animationDelay: '120ms' }}>
              <p className="mb-4 max-w-md text-sm leading-7 text-(--muted)">
                Find the perfect beauty destination. Filter by service, sort by rating, and book your
                appointment in seconds.
              </p>
              <span className="inline-flex items-center gap-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.2em] text-(--terra) uppercase">
                View All Salons →
              </span>
            </div>
          </div>

          <div className="flex flex-col gap-10 lg:flex-row lg:items-start">
            <div className="order-2 min-w-0 flex-1 lg:order-1">
              {!loading && sortedAndFilteredSalons.length > 0 && (
                <div
                  className="hidden md:flex mb-8 flex-col gap-4 border-b-2 border-(--cream) pb-5 sm:flex-row sm:items-center sm:justify-between scroll-animate anim-fade-in-up"
                  style={{ animationDelay: '80ms' }}
                >
                  <span className="text-xs uppercase tracking-[0.2em] text-(--muted)">Sort by</span>
                  <div className="flex flex-wrap gap-2">
                    {[
                      { id: 'rating', label: 'Top Rated' },
                      { id: 'reviews', label: 'Reviews' },
                      { id: 'name', label: 'Name A-Z' },
                      { id: 'newest', label: 'Newest' },
                    ].map((option) => (
                      <button
                        key={option.id}
                        onClick={() => setSortBy(option.id)}
                        className={`px-4 py-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase transition ${
                          sortBy === option.id
                            ? 'bg-(--terra) text-white'
                            : 'bg-(--cream) text-(--muted) hover:text-(--terra)'
                        }`}
                      >
                        {option.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {loading ? (
                <div className="py-20 text-center text-(--muted)">Loading salons...</div>
              ) : sortedAndFilteredSalons.length > 0 ? (
                <div className="grid grid-cols-1 gap-3 md:gap-6 md:grid-cols-2">
                  {sortedAndFilteredSalons.map((salon, index) => (
                    <div
                      key={salon.id}
                      className="scroll-animate anim-slide-in-stagger"
                      style={{ animationDelay: `${Math.min(index, 9) * 80}ms` }}
                    >
                      <SalonCard salon={salon} />
                    </div>
                  ))}
                </div>
              ) : (
                <div className="border-2 border-(--cream) bg-(--white) p-10 text-center scroll-animate anim-scale-in">
                  <h3 className="mb-2 text-2xl font-bold">No salons found</h3>
                  <p className="mb-6 text-sm text-(--muted)">
                    We could not find any salons matching your criteria.
                  </p>
                  <button
                    onClick={() => {
                      setActiveFilter('All');
                      setSearchTerm('');
                    }}
                    className="bg-(--terra) px-6 py-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-(--sienna)"
                  >
                    Reset Filters
                  </button>
                </div>
              )}
            </div>

            <aside
              className="hidden md:block order-1 w-full lg:sticky lg:top-24 lg:order-2 lg:w-[270px] scroll-animate anim-fade-in-right"
              style={{ animationDelay: '160ms' }}
            >
              <div className="overflow-hidden border-2 border-(--cream) bg-(--white)">
                <div className="bg-(--ink) p-6">
                  <div className="mb-2 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra2) uppercase">
                    Filter & Search
                  </div>
                  <h3 className="font-[family-name:Libre_Baskerville] text-2xl italic text-white">Find Your Salon</h3>
                </div>

                <div className="p-6">
                  <form onSubmit={handleSearch} className="mb-6">
                    <div className="flex items-center gap-2 border-2 border-(--cream) bg-(--sand) px-3 py-3">
                      <svg className="h-4 w-4 text-(--muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                      </svg>
                      <input
                        type="text"
                        placeholder="Salon name or location..."
                        className="w-full border-none bg-transparent text-sm text-(--ink) placeholder:text-(--muted) outline-hidden"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                      />
                    </div>
                  </form>

                  <div className="mb-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--muted) uppercase">
                    Categories
                  </div>
                  <div className="mb-6 space-y-1">
                    {filters.map((filter) => (
                      <button
                        key={filter}
                        onClick={() => setActiveFilter(filter)}
                        className={`flex w-full items-center justify-between border-l-[3px] px-3 py-2 text-left text-sm transition ${
                          activeFilter === filter
                            ? 'border-(--sienna) bg-(--terra) text-white'
                            : 'border-transparent text-(--ink) hover:bg-(--cream)'
                        }`}
                      >
                        <span>{filter}</span>
                        <span
                          className={`px-2 py-0.5 font-[family-name:Unbounded] text-[10px] ${
                            activeFilter === filter
                              ? 'bg-white/20 text-white'
                              : 'bg-black/5 text-(--muted)'
                          }`}
                        >
                          {getFilterCount(filter)}
                        </span>
                      </button>
                    ))}
                  </div>

                  <div className="my-5 h-0.5 bg-(--cream)" />

                  <div className="mb-5 flex items-center gap-4 border-l-4 border-(--terra) bg-(--cream) px-4 py-4">
                    <div className="font-[family-name:Unbounded] text-3xl font-black text-(--terra)">
                      {sortedAndFilteredSalons.length}
                    </div>
                    <div>
                      <div className="text-xs text-(--muted)">
                        {sortedAndFilteredSalons.length === 1 ? 'Salon' : 'Salons'} found
                      </div>
                      <div className="text-xs text-(--muted)">Sorted by {sortBy}</div>
                    </div>
                  </div>

                  <div className="my-5 h-0.5 bg-(--cream)" />

                  <div className="mb-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--muted) uppercase">
                    Tips
                  </div>
                  <div className="space-y-2 text-sm text-(--muted)">
                    <div>→ Search by name or location</div>
                    <div>→ Filter by service category</div>
                    <div>→ Sort by rating for best picks</div>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Homepage;
