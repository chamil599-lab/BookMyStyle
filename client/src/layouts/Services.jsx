import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useQuery } from '@tanstack/react-query';
import api, { asArray } from '../utils/api';
import Footer from '../components/Footer';
import useScrollAnimation from '../hooks/useScrollAnimation';

const ServicesPage = () => {
  const scrollRef = useScrollAnimation();
  const [selectedService, setSelectedService] = useState(null);
  const [activeTab, setActiveTab] = useState('all');

  const { data: serviceCategories = [], isLoading } = useQuery({
    queryKey: ['service-pages'],
    queryFn: async () => {
      const response = await api.get('/service-pages');
      const items = asArray(response.data);

      const groups = {};
      const categoryIcons = {
        haircare: 'HC',
        skincare: 'SC',
        nails: 'NL',
        spa: 'SP',
        makeup: 'MU',
        waxing: 'WX',
      };

      items.forEach((item) => {
        const catId = item.category_id || 'uncategorized';
        if (!groups[catId]) {
          groups[catId] = {
            id: catId,
            name: item.category_name || catId,
            icon: categoryIcons[catId] || (item.category_name ? item.category_name.charAt(0).toUpperCase() : 'SV'),
            color: item.category_color || 'theme',
            description: item.category_description || '',
            services: [],
          };
        }

        const features = Array.isArray(item.features)
          ? item.features.map((f) => (typeof f === 'string' ? f : (f.feature ?? String(f))))
          : [];

        groups[catId].services.push({
          name: item.title,
          description: item.description,
          price: item.price,
          duration: item.duration,
          image: item.image,
          features,
          category: groups[catId].name,
          categoryId: groups[catId].id,
          categoryColor: groups[catId].color,
        });
      });

      return Object.values(groups);
    },
  });

  const allServices = serviceCategories.flatMap((cat) =>
    cat.services.map((service) => ({ ...service, category: cat.name, categoryId: cat.id, categoryColor: cat.color })),
  );

  const filteredServices = activeTab === 'all'
    ? allServices
    : allServices.filter((service) => service.categoryId === activeTab);

  const scrollToServices = () => {
    const section = document.getElementById('services-section');
    if (section) section.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div ref={scrollRef} className="min-h-screen bg-[var(--sand)] text-[var(--ink)]">
      <Helmet>
        <title>{`BookMyStyle Services - ${allServices.length}+ Premium Beauty Services`}</title>
        <meta
          name="description"
          content="From haircare to spa treatments, discover our comprehensive range of premium beauty services."
        />
      </Helmet>

      <section className="relative overflow-hidden px-6 py-24 md:px-12 md:py-28">
        <div
          className="absolute inset-0 bg-cover bg-center"
          style={{
            backgroundImage: 'url(\"https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?q=80&w=2069&auto=format&fit=crop\")',
          }}
        />
        <div className="absolute inset-0 bg-black/55" />

        <div className="relative z-10 mx-auto max-w-[1300px]">
          <div className="max-w-3xl">
            <div className="mb-4 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase hero-animate-1">
              <span className="h-0.5 w-5 bg-[var(--terra2)]" />
              Service Collection
            </div>
            <h1 className="font-[Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-white md:text-6xl hero-animate-2">
              Beauty Services
              <br />
              <em className="font-[Libre_Baskerville] text-[var(--terra2)]">Tailored For You</em>
            </h1>
            <p className="mt-5 max-w-2xl text-sm leading-7 text-white/85 md:text-base hero-animate-3">
              Explore hair, skin, nails, spa, makeup, and wellness services from trusted salons.
              Compare options and choose the treatment that fits your style and schedule.
            </p>
            <div className="mt-8 flex flex-wrap items-center gap-6 hero-animate-4">
              <button
                onClick={scrollToServices}
                className="bg-[var(--terra)] px-8 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]"
              >
                Explore Services
              </button>
              <div className="flex flex-wrap gap-5 text-white">
                <div>
                  <div className="font-[Unbounded] text-2xl font-black">{serviceCategories.length}</div>
                  <div className="text-[10px] tracking-[0.2em] uppercase text-white/70">Categories</div>
                </div>
                <div>
                  <div className="font-[Unbounded] text-2xl font-black">{allServices.length}+</div>
                  <div className="text-[10px] tracking-[0.2em] uppercase text-white/70">Services</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="services-section" className="relative overflow-hidden px-6 py-24 md:px-12">
        <div className="pointer-events-none absolute -top-8 right-4 select-none font-[Unbounded] text-[16rem] font-black leading-none text-transparent [-webkit-text-stroke:1px_rgba(196,98,45,0.07)] md:text-[20rem]">
          V
        </div>

        <div className="relative z-10 mx-auto max-w-[1300px]">
          <div className="mb-16 grid items-end gap-10 lg:grid-cols-2">
            <div className="scroll-animate anim-fade-in-left">
              <div className="mb-4 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase">
                <span className="h-0.5 w-5 bg-[var(--terra)]" />
                Our Services
              </div>
              <h2 className="font-[Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-[var(--ink)] md:text-6xl">
                Premium Beauty
                <br />
                <em className="font-[Libre_Baskerville] text-[var(--terra)]">Services</em>
              </h2>
            </div>
            <div className="scroll-animate anim-fade-in-right" style={{ animationDelay: '120ms' }}>
              <p className="mb-4 max-w-md text-sm leading-7 text-[var(--muted)]">
                From haircare to spa treatments, discover our comprehensive range of beauty services.
              </p>
              {!isLoading && (
                <div className="flex flex-wrap gap-6">
                  <div>
                    <div className="font-[Unbounded] text-2xl font-black text-[var(--terra)]">{serviceCategories.length}</div>
                    <div className="text-xs uppercase tracking-[0.15em] text-[var(--muted)]">Categories</div>
                  </div>
                  <div>
                    <div className="font-[Unbounded] text-2xl font-black text-[var(--terra)]">{allServices.length}+</div>
                    <div className="text-xs uppercase tracking-[0.15em] text-[var(--muted)]">Services</div>
                  </div>
                  <div>
                    <div className="font-[Unbounded] text-2xl font-black text-[var(--terra)]">500+</div>
                    <div className="text-xs uppercase tracking-[0.15em] text-[var(--muted)]">Experts</div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {isLoading ? (
            <div className="py-20 text-center text-[var(--muted)]">Loading services...</div>
          ) : (
            <>
              <div className="mb-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                {serviceCategories.map((category, index) => (
                  <button
                    key={category.id}
                    onClick={() => setActiveTab(category.id)}
                    style={{ animationDelay: `${Math.min(index, 5) * 70}ms` }}
                    className={`border-2 p-5 text-center transition ${
                      activeTab === category.id
                        ? 'border-[var(--sienna)] bg-[var(--terra)] text-white'
                        : 'border-[var(--cream)] bg-[var(--white)] text-[var(--ink)] hover:border-[var(--terra)]'
                    } scroll-animate anim-slide-in-stagger`}
                  >
                    <div className="mb-2 font-[Unbounded] text-sm font-black">{category.icon}</div>
                    <div className="text-xs font-semibold uppercase tracking-[0.1em]">{category.name}</div>
                  </button>
                ))}
              </div>

              <div className="mb-10 text-center scroll-animate anim-fade-in-up" style={{ animationDelay: '100ms' }}>
                <button
                  onClick={() => setActiveTab('all')}
                  className={`px-8 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] uppercase transition ${
                    activeTab === 'all'
                      ? 'bg-[var(--terra)] text-white'
                      : 'bg-[var(--cream)] text-[var(--muted)] hover:text-[var(--terra)]'
                  }`}
                >
                  View All Services
                </button>
              </div>

              <section className="mb-20">
                {activeTab === 'all' ? (
                  serviceCategories.map((category) => (
                    <div key={category.id} className="mb-14">
                      <div className="mb-6 flex items-center gap-4 border-b-2 border-[var(--cream)] pb-4 scroll-animate anim-fade-in-up">
                        <div className="flex h-12 w-12 items-center justify-center bg-[var(--cream)] font-[Unbounded] text-xs font-black text-[var(--terra)]">
                          {category.icon}
                        </div>
                        <div>
                          <h3 className="text-2xl font-black text-[var(--ink)]">{category.name}</h3>
                          <p className="text-sm text-[var(--muted)]">{category.description}</p>
                        </div>
                      </div>

                      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {category.services.map((service, index) => (
                          <ServiceCard
                            key={`${category.id}-${index}`}
                            service={{ ...service, categoryColor: category.color }}
                            onClick={() => setSelectedService({ ...service, categoryColor: category.color, category: category.name })}
                            animationDelay={`${Math.min(index, 7) * 70}ms`}
                          />
                        ))}
                      </div>
                    </div>
                  ))
                ) : (
                  <>
                    <div className="mb-6 text-center text-sm text-[var(--muted)] scroll-animate anim-fade-in-up">
                      <span className="font-bold text-[var(--terra)]">{filteredServices.length}</span> services available
                    </div>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                      {filteredServices.map((service, index) => (
                        <ServiceCard
                          key={`filtered-${index}`}
                          service={service}
                          onClick={() => setSelectedService(service)}
                          animationDelay={`${Math.min(index, 7) * 70}ms`}
                        />
                      ))}
                    </div>
                  </>
                )}
              </section>

              <section className="mb-20 border-2 border-[var(--cream)] bg-[var(--white)] p-8 md:p-12 scroll-animate anim-fade-in-up">
                <div className="mb-10 text-center">
                  <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold tracking-tight text-[var(--ink)] md:text-5xl">
                    Why Choose Our Services?
                  </h2>
                  <p className="text-[var(--muted)]">Experience the difference with BookMyStyle</p>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                  {[
                    {
                      title: 'Verified Experts',
                      description: 'All professionals are certified and experienced',
                    },
                    {
                      title: 'Premium Products',
                      description: 'We use only the best quality products',
                    },
                    {
                      title: 'Flexible Timing',
                      description: 'Book appointments at your convenience',
                    },
                    {
                      title: 'Fair Pricing',
                      description: 'Transparent pricing with no hidden costs',
                    },
                  ].map((feature, index) => (
                    <div key={index} className="border-2 border-[var(--cream)] bg-[var(--sand)] p-6 text-center scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${index * 80}ms` }}>
                      <div className="mx-auto mb-4 h-12 w-12 bg-[var(--terra)]" />
                      <h3 className="mb-2 text-lg font-bold text-[var(--ink)]">{feature.title}</h3>
                      <p className="text-sm text-[var(--muted)]">{feature.description}</p>
                    </div>
                  ))}
                </div>
              </section>

              <section className="border-2 border-[var(--cream)] bg-[var(--ink)] p-10 text-center md:p-16 scroll-animate anim-scale-in">
                <h2 className="mb-4 font-[Unbounded] text-3xl font-extrabold text-white md:text-4xl">
                  Ready to Book Your Service?
                </h2>
                <p className="mx-auto mb-8 max-w-2xl text-white/80">
                  Choose from our wide range of services and book your appointment today
                </p>
                <button className="bg-[var(--terra)] px-10 py-4 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]">
                  Book Now
                </button>
              </section>
            </>
          )}
        </div>
      </section>

      {selectedService && (
        <ServiceModal
          service={selectedService}
          onClose={() => setSelectedService(null)}
        />
      )}

      <Footer />
    </div>
  );
};

const ServiceCard = ({ service, onClick, animationDelay = '0ms' }) => {
  const image = service.image || 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&q=80';

  return (
    <button
      type="button"
      onClick={onClick}
      style={{ animationDelay }}
      className="group overflow-hidden border-2 border-[var(--cream)] bg-[var(--white)] text-left transition hover:border-[var(--terra)] scroll-animate anim-slide-in-stagger"
    >
      <div className="relative h-48 overflow-hidden">
        <img
          src={image}
          alt={service.name}
          className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent" />
        <div className="absolute right-3 top-3 bg-[var(--terra)] px-2 py-1 font-[Unbounded] text-[10px] font-semibold tracking-[0.15em] text-white uppercase">
          {service.duration}
        </div>
      </div>

      <div className="p-5">
        <h3 className="mb-2 text-lg font-bold text-[var(--ink)] group-hover:text-[var(--terra)]">{service.name}</h3>
        <p className="mb-4 line-clamp-2 text-sm text-[var(--muted)]">{service.description}</p>

        <div className="flex items-center justify-between border-t border-[var(--cream)] pt-3">
          <span className="font-[Unbounded] text-[12px] font-semibold tracking-[0.12em] text-[var(--ink)] uppercase">
            {service.price}
          </span>
          <span className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--terra)]">View Details</span>
        </div>
      </div>
    </button>
  );
};

const ServiceModal = ({ service, onClose }) => {
  const image = service.image || 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&q=80';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        className="max-h-[90vh] w-full max-w-3xl overflow-y-auto border-2 border-[var(--cream)] bg-[var(--white)]"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="relative h-72">
          <img
            src={image}
            alt={service.name}
            className="h-full w-full object-cover"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/65 to-transparent" />
          <button
            onClick={onClose}
            className="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-black/40 text-white"
            aria-label="Close service details"
          >
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <div className="absolute bottom-6 left-6 right-6">
            <div className="mb-2 inline-block bg-[var(--terra)] px-3 py-1 font-[Unbounded] text-[10px] font-semibold tracking-[0.15em] text-white uppercase">
              {service.category}
            </div>
            <h2 className="text-3xl font-black text-white md:text-4xl">{service.name}</h2>
          </div>
        </div>

        <div className="space-y-6 p-8">
          <div className="flex items-center gap-8 border-b border-[var(--cream)] pb-6">
            <div>
              <div className="mb-1 text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Price</div>
              <div className="text-2xl font-black text-[var(--sienna)]">{service.price}</div>
            </div>
            <div className="h-12 w-px bg-[var(--cream)]" />
            <div>
              <div className="mb-1 text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Duration</div>
              <div className="text-lg font-semibold text-[var(--ink)]">{service.duration}</div>
            </div>
          </div>

          <div>
            <h3 className="mb-3 font-[Unbounded] text-sm font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">Description</h3>
            <p className="leading-relaxed text-[var(--muted)]">{service.description}</p>
          </div>

          <div>
            <h3 className="mb-3 font-[Unbounded] text-sm font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">What's Included</h3>
            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
              {(service.features || []).map((feature, index) => (
                <div key={`${feature}-${index}`} className="flex items-center gap-2 bg-[var(--sand)] px-3 py-2 text-sm text-[var(--ink)]">
                  <span className="h-1.5 w-1.5 rounded-full bg-[var(--terra)]" />
                  {feature}
                </div>
              ))}
            </div>
          </div>

          <button className="w-full bg-[var(--terra)] px-6 py-4 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]">
            Coming Soon
          </button>
        </div>
      </div>
    </div>
  );
};

export default ServicesPage;
