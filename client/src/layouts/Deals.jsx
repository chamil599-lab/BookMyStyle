import { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { useQuery } from '@tanstack/react-query';
import api, { asArray } from '../utils/api';
import Footer from '../components/Footer';
import useScrollAnimation from '../hooks/useScrollAnimation';

const DealsPage = () => {
  const scrollRef = useScrollAnimation();
  const { hash } = useLocation();
  const [activeCategory, setActiveCategory] = useState('all');

  useEffect(() => {
    if (!hash) return;
    const target = document.getElementById(hash.slice(1));
    if (target) {
      requestAnimationFrame(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    }
  }, [hash]);

  const categories = [
    { id: 'all', label: 'All Deals', icon: 'AD' },
    { id: 'haircare', label: 'Hair Care', icon: 'HC' },
    { id: 'skincare', label: 'Skin Care', icon: 'SC' },
    { id: 'nails', label: 'Nails', icon: 'NL' },
    { id: 'spa', label: 'Spa & Wellness', icon: 'SP' },
    { id: 'makeup', label: 'Makeup', icon: 'MU' },
  ];

  const { data: deals = [], isLoading } = useQuery({
    queryKey: ['deals'],
    queryFn: async () => {
      const response = await api.get('/dealspages');

      return asArray(response.data).map((d) => ({
        id: d.id,
        category: d.category,
        title: d.title,
        salon: d.salon,
        originalPrice: d.originalPrice,
        discountedPrice: d.discountedPrice,
        discount: d.discount,
        image: d.image,
        badge: d.badge,
        badgeColor: d.badgeColor,
        description: d.description,
        validUntil: d.validUntil,
        features: Array.isArray(d.features) ? d.features : [],
      }));
    },
  });

  const filteredDeals = activeCategory === 'all'
    ? deals
    : deals.filter((deal) => deal.category === activeCategory);

  const flashDeals = deals.filter((deal) => ['Hot Deal', 'Limited'].includes(deal.badge)).slice(0, 3);

  return (
    <div ref={scrollRef} className="min-h-screen bg-[var(--sand)] text-[var(--ink)]">
      <Helmet>
        <title>{`BookMyStyle Deals - ${deals.length}+ Active Beauty Offers`}</title>
        <meta
          name="description"
          content="Save big on premium beauty services. Discover limited-time offers and exclusive salon discounts."
        />
      </Helmet>

      <section className="relative overflow-hidden px-6 py-24 md:px-12 md:py-28">
        <div
          className="absolute inset-0 bg-cover bg-center"
          style={{
            backgroundImage: 'url("https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=2074&auto=format&fit=crop")',
          }}
        />
        <div className="absolute inset-0 bg-black/55" />

        <div className="relative z-10 mx-auto max-w-[1300px] text-center">
          <div className="mx-auto max-w-4xl">
            <div className="mb-5 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase hero-animate-1">
              <span className="h-0.5 w-5 bg-[var(--terra2)]" />
              Limited Time Offers
            </div>

            <h1 className="font-[Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-white md:text-6xl hero-animate-2">
              Exclusive Beauty
              <br />
              <em className="font-[Libre_Baskerville] text-[var(--terra2)]">Deals & Offers</em>
            </h1>

            <p className="mx-auto mt-5 max-w-3xl text-sm leading-7 text-white/85 md:text-base hero-animate-3">
              Save big on premium beauty services. Don't miss out on these amazing deals!
            </p>

            {!isLoading && (
              <div className="mt-8 flex flex-wrap items-center justify-center gap-8 text-white hero-animate-4">
                <div>
                  <div className="font-[Unbounded] text-3xl font-black">40%</div>
                  <div className="text-[10px] tracking-[0.2em] uppercase text-white/70">Max Discount</div>
                </div>
                <div>
                  <div className="font-[Unbounded] text-3xl font-black">{deals.length}+</div>
                  <div className="text-[10px] tracking-[0.2em] uppercase text-white/70">Active Deals</div>
                </div>
                <div>
                  <div className="font-[Unbounded] text-3xl font-black">50+</div>
                  <div className="text-[10px] tracking-[0.2em] uppercase text-white/70">Partner Salons</div>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>

      {isLoading ? (
        <section className="px-6 py-24">
          <div className="mx-auto max-w-7xl text-center text-[var(--muted)]">Loading amazing deals...</div>
        </section>
      ) : (
        <>
          {flashDeals.length > 0 && (
            <section className="px-6 pb-12">
              <div className="mx-auto max-w-7xl border-2 border-[var(--cream)] bg-[var(--ink)] p-8 md:p-10 scroll-animate anim-fade-in-up">
                <div className="mb-8 text-center">
                  <h2 className="font-[Unbounded] text-2xl font-extrabold text-white md:text-3xl">Flash Deals - Ending Soon!</h2>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                  {flashDeals.map((deal, index) => (
                    <div key={deal.id} className="border-2 border-[var(--cream)] bg-[var(--white)] p-5 scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${index * 80}ms` }}>
                      <div className="mb-4 flex items-start justify-between gap-3">
                        <div>
                          <h3 className="mb-1 text-lg font-bold text-[var(--ink)]">{deal.title}</h3>
                          <p className="text-sm text-[var(--muted)]">{deal.salon}</p>
                        </div>
                        <div className="bg-[var(--terra)] px-3 py-1 text-xs font-bold text-white">-{deal.discount}%</div>
                      </div>
                      <div className="flex items-center gap-3">
                        <span className="text-xl font-black text-[var(--sienna)]">Rs. {Number(deal.discountedPrice || 0).toLocaleString()}</span>
                        <span className="text-sm text-[var(--muted)] line-through">Rs. {Number(deal.originalPrice || 0).toLocaleString()}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </section>
          )}

          <section className="px-6 py-10">
            <div className="mx-auto max-w-7xl">
              <div className="mb-8 flex flex-wrap justify-center gap-3">
                {categories.map((category, index) => (
                  <button
                    key={category.id}
                    onClick={() => setActiveCategory(category.id)}
                    style={{ animationDelay: `${Math.min(index, 5) * 70}ms` }}
                    className={`px-5 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.15em] uppercase transition ${
                      activeCategory === category.id
                        ? 'bg-[var(--terra)] text-white'
                        : 'bg-[var(--cream)] text-[var(--muted)] hover:text-[var(--terra)]'
                    } scroll-animate anim-slide-in-stagger`}
                  >
                    <span className="mr-2">{category.icon}</span>
                    {category.label}
                  </button>
                ))}
              </div>

              <div className="mb-8 text-center text-sm text-[var(--muted)] scroll-animate anim-fade-in-up" style={{ animationDelay: '120ms' }}>
                <span className="font-bold text-[var(--terra)]">{filteredDeals.length}</span> amazing deals available
              </div>
            </div>
          </section>

          <section className="px-6 pb-20">
            <div className="mx-auto max-w-7xl">
              {filteredDeals.length > 0 ? (
                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                  {filteredDeals.map((deal, index) => (
                    <article key={deal.id} className="overflow-hidden border-2 border-[var(--cream)] bg-[var(--white)] transition hover:border-[var(--terra)] scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${Math.min(index, 8) * 80}ms` }}>
                      <div className="relative h-56 overflow-hidden">
                        <img
                          src={deal.image}
                          alt={deal.title}
                          className="h-full w-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent" />

                        <div className="absolute left-4 top-4 bg-[var(--terra)] px-3 py-1 text-xs font-semibold text-white">
                          {deal.badge}
                        </div>

                        <div className="absolute right-4 top-4 bg-[var(--white)] px-3 py-2 text-center">
                          <div className="text-lg font-black text-[var(--sienna)]">{deal.discount}%</div>
                          <div className="text-[10px] font-semibold uppercase tracking-[0.15em] text-[var(--muted)]">Off</div>
                        </div>
                      </div>

                      <div className="p-6">
                        <h3 className="mb-2 text-xl font-bold text-[var(--ink)]">{deal.title}</h3>

                        <div className="mb-3 text-sm text-[var(--muted)]">{deal.salon}</div>

                        <p className="mb-4 text-sm leading-relaxed text-[var(--muted)]">{deal.description}</p>

                        {deal.features.length > 0 && (
                          <div className="mb-4 flex flex-wrap gap-2">
                            {deal.features.map((feature, index) => (
                              <span key={index} className="bg-[var(--sand)] px-2 py-1 text-xs text-[var(--ink)]">
                                {feature}
                              </span>
                            ))}
                          </div>
                        )}

                        <div className="mb-4 flex items-end gap-3">
                          <div className="text-2xl font-black text-[var(--sienna)]">
                            Rs. {Number(deal.discountedPrice || 0).toLocaleString()}
                          </div>
                          <div className="pb-1 text-sm text-[var(--muted)] line-through">
                            Rs. {Number(deal.originalPrice || 0).toLocaleString()}
                          </div>
                        </div>

                        {deal.validUntil && (
                          <div className="mb-4 text-xs text-[var(--muted)]">
                            Valid until {new Date(deal.validUntil).toLocaleDateString()}
                          </div>
                        )}

                        <button className="w-full bg-[var(--terra)] py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]">
                          Coming Soon
                        </button>
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <div className="border-2 border-[var(--cream)] bg-[var(--white)] px-6 py-16 text-center scroll-animate anim-scale-in">
                  <h3 className="mb-3 text-2xl font-bold text-[var(--ink)]">No deals found</h3>
                  <p className="mb-6 text-[var(--muted)]">We couldn't find any deals in this category.</p>
                  <button
                    onClick={() => setActiveCategory('all')}
                    className="bg-[var(--terra)] px-8 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]"
                  >
                    View All Deals
                  </button>
                </div>
              )}
            </div>
          </section>

          <section id="how-it-works" className="px-6 pb-20 scroll-mt-24">
            <div className="mx-auto max-w-6xl border-2 border-[var(--cream)] bg-[var(--white)] p-8 md:p-12 scroll-animate anim-fade-in-up">
              <div className="mb-12 text-center">
                <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">How It Works</h2>
                <p className="text-[var(--muted)]">Book your deal in three simple steps</p>
              </div>

              <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                {[
                  {
                    step: '1',
                    title: 'Choose Your Deal',
                    description: 'Browse through our exclusive deals and select the one that suits you best',
                  },
                  {
                    step: '2',
                    title: 'Book Appointment',
                    description: 'Select your preferred date and time slot for the service',
                  },
                  {
                    step: '3',
                    title: 'Enjoy Service',
                    description: 'Visit the salon and enjoy your premium service at a discounted price',
                  },
                ].map((item, index) => (
                  <div key={item.step} className="border-2 border-[var(--cream)] bg-[var(--sand)] p-6 text-center scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${index * 90}ms` }}>
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center bg-[var(--terra)] font-[Unbounded] text-lg font-black text-white">
                      {item.step}
                    </div>
                    <h3 className="mb-2 text-xl font-bold text-[var(--ink)]">{item.title}</h3>
                    <p className="text-sm leading-relaxed text-[var(--muted)]">{item.description}</p>
                  </div>
                ))}
              </div>
            </div>
          </section>

          <section className="px-6 pb-24">
            <div className="mx-auto max-w-5xl border-2 border-[var(--cream)] bg-[var(--ink)] p-10 text-center md:p-14 scroll-animate anim-scale-in">
              <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold text-white md:text-4xl">Never Miss a Deal!</h2>
              <p className="mx-auto mb-8 max-w-2xl text-white/80">
                Subscribe to our newsletter and get exclusive deals delivered to your inbox
              </p>
              <div className="mx-auto flex max-w-md flex-col gap-3 sm:flex-row">
                <input
                  type="email"
                  placeholder="Enter your email"
                  className="flex-1 bg-white px-5 py-3 text-sm text-[var(--ink)] outline-none"
                />
                <button className="bg-[var(--terra)] px-6 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]">
                  Subscribe Now
                </button>
              </div>
            </div>
          </section>
        </>
      )}

      <Footer />
    </div>
  );
};

export default DealsPage;
