import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api, { asArray, asObject } from '../utils/api';
import Footer from '../components/Footer';
import useScrollAnimation from '../hooks/useScrollAnimation';

const AboutPage = () => {
  const scrollRef = useScrollAnimation();
  const [activeTab, setActiveTab] = useState('mission');

  const { data: statsData, isLoading: statsLoading } = useQuery({
    queryKey: ['stats'],
    queryFn: async () => {
      const response = await api.get('/stats');
      const payload = asObject(response?.data);

      return {
        totalSalons: payload.totalSalons ?? payload.totalSalonsCount ?? payload.salons_count ?? 0,
        totalClients: payload.totalClients ?? payload.uniqueClients ?? payload.clients_count ?? 0,
        bookingsMade: payload.totalBookings ?? payload.approvedBookings ?? payload.bookings ?? payload.count ?? 0,
      };
    },
  });

  const stats = [
    { number: statsLoading ? '...' : (statsData?.totalSalons ? `${statsData.totalSalons}+` : '500+'), label: 'Partner Salons', icon: 'PS' },
    { number: statsLoading ? '...' : (statsData?.totalClients ? `${statsData.totalClients}+` : '50K+'), label: 'Happy Clients', icon: 'HC' },
    { number: statsLoading ? '...' : (statsData?.bookingsMade ? `${statsData.bookingsMade}+` : '100K+'), label: 'Bookings Made', icon: 'BM' },
    { number: '25+', label: 'Cities Covered', icon: 'CC' },
  ];

  const values = [
    {
      title: 'Quality First',
      description: 'We partner only with verified, top-rated salons that meet our high standards',
      icon: (
        <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      ),
    },
    {
      title: 'Customer Centric',
      description: 'Your satisfaction is our priority. We listen, improve, and deliver excellence',
      icon: (
        <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      ),
    },
    {
      title: 'Innovation',
      description: 'We constantly evolve our platform to provide the best booking experience',
      icon: (
        <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      ),
    },
    {
      title: 'Trust & Transparency',
      description: 'Honest reviews, clear pricing, and secure transactions - always',
      icon: (
        <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      ),
    },
  ];

  const fallbackTeam = [
    {
      name: 'Chamil Dissanayake',
      role: 'Founder of Limach Web Solutions',
      image: 'https://scontent.fcmb1-2.fna.fbcdn.net/v/t39.30808-6/469471402_4028370097396800_1317620920268141588_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeHEdIzTVJ4hWLBT6_Za_L591411zxTf5rnXjXXPFN_mua7qyLV-WJ61U64zbBNJDD_1CJCoMY378LdjKn2pGm2e&_nc_ohc=3IGKeFw5RMoQ7kNvwGGEDIN&_nc_oc=AdlCSqvoG-C7eR-eEISGsKtvrj9J3pgWY6AVzx6qgb5Do2zX__nejMMZERWTWpDHUDc06utOa5lNxTYsrx36GgHH&_nc_zt=23&_nc_ht=scontent.fcmb1-2.fna&_nc_gid=5n23FG5ibfWM-s8Xc9ziLA&oh=00_Afne7Ucc85t8544QmjZjT38sbQdvvdr8FiySlLptX2zz7Q&oe=695B33CE',
      bio: 'Delivering tailored web and digital solutions for modern businesses.',
    },
    {
      name: 'Michael Chen',
      role: 'Chief Technology Officer',
      image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=80',
      bio: 'Tech innovator passionate about creating seamless user experiences',
    },
    {
      name: 'Emily Rodriguez',
      role: 'Head of Partnerships',
      image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&q=80',
      bio: 'Building relationships with premium salons across Sri Lanka',
    },
    {
      name: 'David Kumar',
      role: 'Customer Experience Lead',
      image: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&q=80',
      bio: 'Ensuring every customer has a delightful booking experience',
    },
  ];

  const { data: teamData, isLoading: teamLoading } = useQuery({
    queryKey: ['teamMembers'],
    queryFn: async () => {
      const res = await api.get('/team-members');
      return asArray(res.data);
    },
    staleTime: 1000 * 60 * 5,
  });

  const teamMembers = teamData && !teamLoading ? teamData : fallbackTeam;
  const teamCount = teamMembers.length;
  const teamGridClass =
    teamCount <= 1
      ? 'grid-cols-1 max-w-md'
      : teamCount === 2
        ? 'grid-cols-1 md:grid-cols-2 max-w-4xl'
        : teamCount === 3
          ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3 max-w-6xl'
          : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4 max-w-6xl';

  return (
    <div ref={scrollRef} className="min-h-screen bg-[var(--sand)] text-[var(--ink)]">
      <Helmet>
        <title>About BookMyStyle - Redefining Beauty Bookings in Sri Lanka</title>
      </Helmet>

      <section className="relative px-6 py-24 overflow-hidden md:px-12 md:py-28">
        <div
          className="absolute inset-0 bg-center bg-cover"
          style={{
            backgroundImage: 'url("https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?q=80&w=2070&auto=format&fit=crop")',
          }}
        />
        <div className="absolute inset-0 bg-black/55" />

        <div className="relative z-10 mx-auto max-w-[1300px] text-center">
          <div className="max-w-4xl mx-auto">
            <div className="mb-5 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase hero-animate-1">
              <span className="h-0.5 w-5 bg-[var(--terra2)]" />
              About BookMyStyle
            </div>

            <h1 className="font-[Unbounded] text-4xl font-extrabold leading-[0.95] tracking-tight text-white md:text-6xl hero-animate-2">
              Redefining Beauty
              <br />
              <em className="font-[Libre_Baskerville] text-[var(--terra2)]">Bookings in Sri Lanka</em>
            </h1>

            <p className="max-w-3xl mx-auto mt-5 text-sm leading-7 text-white/85 md:text-base hero-animate-3">
              We're on a mission to make beauty accessible, convenient, and delightful for everyone.
            </p>
          </div>
        </div>
      </section>

      <section className="px-6 pb-16">
        <div className="grid max-w-6xl grid-cols-2 gap-6 mx-auto md:grid-cols-4">
          {stats.map((stat, index) => (
            <div key={index} className="border-2 border-[var(--cream)] bg-[var(--white)] p-6 text-center scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${index * 80}ms` }}>
              <div className="mb-3 font-[Unbounded] text-xs font-black tracking-[0.15em] text-[var(--terra)] uppercase">{stat.icon}</div>
              <div className="mb-2 font-[Unbounded] text-3xl font-black text-[var(--sienna)] md:text-4xl">{stat.number}</div>
              <div className="text-sm text-[var(--muted)]">{stat.label}</div>
            </div>
          ))}
        </div>
      </section>

      <section className="px-6 pb-16">
        <div className="max-w-6xl mx-auto">
          <div className="flex flex-wrap justify-center gap-3 mb-10">
            {['mission', 'vision', 'story'].map((tab, index) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                style={{ animationDelay: `${index * 80}ms` }}
                className={`px-7 py-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] uppercase transition ${
                  activeTab === tab
                    ? 'bg-[var(--terra)] text-white'
                    : 'bg-[var(--cream)] text-[var(--muted)] hover:text-[var(--terra)]'
                } scroll-animate anim-slide-in-stagger`}
              >
                {tab}
              </button>
            ))}
          </div>

          <div className="border-2 border-[var(--cream)] bg-[var(--white)] p-8 md:p-12 scroll-animate anim-fade-in-up" style={{ animationDelay: '120ms' }}>
            {activeTab === 'mission' && (
              <div className="max-w-4xl mx-auto text-center">
                <div className="mx-auto mb-6 flex h-14 w-14 items-center justify-center bg-[var(--terra)] text-white">
                  <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h2 className="mb-5 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">Our Mission</h2>
                <p className="mb-5 text-lg leading-relaxed text-[var(--ink)]">
                  To empower people to look and feel their best by connecting them with Sri Lanka's finest salons and beauty professionals through an effortless, transparent, and delightful booking experience.
                </p>
                <p className="leading-relaxed text-[var(--muted)]">
                  We believe everyone deserves access to quality beauty services without the hassle of endless phone calls, uncertainty about availability, or concerns about service quality. That's why we've built a platform that makes booking your next appointment as simple as a few taps.
                </p>
              </div>
            )}

            {activeTab === 'vision' && (
              <div className="max-w-4xl mx-auto text-center">
                <div className="mx-auto mb-6 flex h-14 w-14 items-center justify-center bg-[var(--terra)] text-white">
                  <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </div>
                <h2 className="mb-5 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">Our Vision</h2>
                <p className="mb-5 text-lg leading-relaxed text-[var(--ink)]">
                  To become Sri Lanka's most trusted beauty and wellness platform, setting the standard for how people discover, book, and experience salon services across the nation and beyond.
                </p>
                <p className="leading-relaxed text-[var(--muted)]">
                  We envision a future where booking beauty services is as natural as ordering food online - instant, reliable, and personalized. A future where every salon, from boutique studios to premium chains, has the tools to thrive, and every customer can confidently find their perfect style match.
                </p>
              </div>
            )}

            {activeTab === 'story' && (
              <div className="max-w-4xl mx-auto text-center">
                <div className="mx-auto mb-6 flex h-14 w-14 items-center justify-center bg-[var(--terra)] text-white">
                  <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <h2 className="mb-5 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">Our Story</h2>
                <p className="mb-5 text-lg leading-relaxed text-[var(--ink)]">
                  BookMyStyle was born from a simple frustration: Why is booking a salon appointment so complicated in 2020?
                </p>
                <p className="mb-5 leading-relaxed text-[var(--muted)]">
                  Our founder, after spending hours calling different salons trying to find an available slot for a simple haircut, realized there had to be a better way. That evening, the idea for BookMyStyle was sketched on a napkin at a local cafe.
                </p>
                <p className="leading-relaxed text-[var(--muted)]">
                  What started as a weekend project quickly evolved into a full-fledged platform. We partnered with our first 10 salons in Colombo, and within months, had facilitated hundreds of bookings. Today, we're proud to be Sri Lanka's leading beauty booking platform, serving thousands of happy customers every week.
                </p>
              </div>
            )}
          </div>
        </div>
      </section>

      <section className="px-6 pb-16">
        <div className="max-w-6xl mx-auto">
          <div className="mb-10 text-center scroll-animate anim-fade-in-up">
            <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">Our Core Values</h2>
            <p className="text-[var(--muted)]">The principles that guide everything we do</p>
          </div>

          <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
            {values.map((value, index) => (
              <div key={index} className="border-2 border-[var(--cream)] bg-[var(--white)] p-7 scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${index * 80}ms` }}>
                <div className="mb-5 flex h-12 w-12 items-center justify-center bg-[var(--terra)] text-white">{value.icon}</div>
                <h3 className="mb-3 text-2xl font-bold text-[var(--ink)]">{value.title}</h3>
                <p className="leading-relaxed text-[var(--muted)]">{value.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="px-6 pb-16">
        <div className="mx-auto">
          <div className="mb-10 text-center scroll-animate anim-fade-in-up">
            <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold text-[var(--ink)] md:text-4xl">Meet Our Team</h2>
            <p className="text-[var(--muted)]">The people making beauty bookings magical</p>
          </div>

          <div className={`mx-auto grid gap-6 ${teamGridClass}`}>
            {teamMembers.map((member, index) => {
              const imageUrl = member.image_url || member.src_url || member.upload_path || member.image || '';

              return (
                <div key={member.id ?? index} className="overflow-hidden border-2 border-[var(--cream)] bg-[var(--white)] scroll-animate anim-slide-in-stagger" style={{ animationDelay: `${Math.min(index, 7) * 80}ms` }}>
                  <div className="overflow-hidden h-72">
                    <img src={imageUrl} alt={member.name} className="object-cover w-full h-full" />
                  </div>
                  <div className="p-6">
                    <h3 className="mb-2 text-xl font-bold text-[var(--ink)]">{member.name}</h3>
                    <p className="mb-3 font-semibold text-[var(--terra)]">{member.role}</p>
                    <p className="text-sm leading-relaxed text-[var(--muted)]">{member.bio}</p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      <section className="px-6 pb-24">
        <div className="mx-auto max-w-5xl border-2 border-[var(--cream)] bg-[var(--ink)] p-10 text-center md:p-14 scroll-animate anim-scale-in">
          <h2 className="mb-3 font-[Unbounded] text-3xl font-extrabold text-white md:text-4xl">Join Our Journey</h2>
          <p className="max-w-2xl mx-auto mb-8 text-white/80">
            Whether you're a customer or a salon owner, we'd love to have you be part of our story
          </p>
          <div className="flex flex-col justify-center gap-4 sm:flex-row">
            <Link
              to="/salons"
              className="bg-[var(--terra)] px-8 py-4 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--sienna)]"
            >
              Book Now
            </Link>
            <a
              href="https://bookmystyle.limach.lk/admin/login"
              target="_blank"
              rel="noreferrer"
              className="border-2 border-white/30 bg-transparent px-8 py-4 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-white/10"
            >
              Partner Login
            </a>
            <a
              href="https://bookmystyle.limach.lk/admin/register"
              target="_blank"
              rel="noreferrer"
              className="border-2 border-[var(--terra2)] bg-[var(--terra2)]/10 px-8 py-4 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-white uppercase hover:bg-[var(--terra2)]/20"
            >
              Partner Register
            </a>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default AboutPage;
