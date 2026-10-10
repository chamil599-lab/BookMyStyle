import { Helmet } from 'react-helmet-async';
import { useLocation } from 'react-router-dom';

const SITE_URL = (import.meta.env.VITE_SITE_URL || 'https://bookmystyle.limach.lk').replace(/\/$/, '');
const DEFAULT_IMAGE = `${SITE_URL}/logo2.png`;

const ROUTE_META = {
  '/': {
    title: 'BookMyStyle - Book Beauty Salon & Spa Appointments Online in Sri Lanka',
    description:
      'Book appointments at top-rated beauty salons and spas in Sri Lanka. Browse services, compare salons, read reviews, and book online instantly.',
  },
  '/salons': {
    title: 'Browse Beauty Salons in Sri Lanka | BookMyStyle',
    description:
      'Find verified salons and spas in Sri Lanka. Search by service, location, rating, and book your next beauty appointment online.',
  },
  '/services': {
    title: 'Beauty Salon Services in Sri Lanka | BookMyStyle',
    description:
      'Explore hair, nail, makeup, skincare, spa, and bridal beauty services from trusted Sri Lankan salons.',
  },
  '/deals': {
    title: 'Beauty Salon Deals & Offers in Sri Lanka | BookMyStyle',
    description:
      'Discover limited-time beauty deals, salon discounts, and spa offers from BookMyStyle partner salons.',
  },
  '/about': {
    title: 'About BookMyStyle | Sri Lanka Beauty Booking Platform',
    description:
      'Learn about BookMyStyle, a Sri Lankan platform helping customers discover, compare, and book trusted salons online.',
  },
  '/trending': {
    title: 'Trending Salons in Sri Lanka | BookMyStyle',
    description: 'Discover trending beauty salons, popular treatments, and highly rated salon experiences in Sri Lanka.',
  },
  '/popular': {
    title: 'Most Popular Salons in Sri Lanka | BookMyStyle',
    description: 'Browse popular salons and beauty services trusted by BookMyStyle customers.',
  },
  '/terms-and-condition': {
    title: 'Terms and Conditions | BookMyStyle',
    description: 'Read the BookMyStyle terms and conditions for customers and salon partners.',
  },
  '/privacy-policy': {
    title: 'Privacy Policy | BookMyStyle',
    description: 'Read how BookMyStyle protects your privacy and handles customer and partner data.',
  },
};

const PRIVATE_PREFIXES = ['/dashboard', '/admin', '/owner'];
const AUTH_PATHS = ['/login', '/register', '/forgot-password', '/reset-password'];

function getRouteMeta(pathname) {
  if (pathname.startsWith('/salon/')) {
    return {
      title: 'Book Salon Appointment Online | BookMyStyle',
      description:
        'View salon details, services, gallery, reviews, and book a beauty appointment online with BookMyStyle.',
    };
  }

  return ROUTE_META[pathname] || ROUTE_META['/'];
}

export default function SEO({ title, description, image = DEFAULT_IMAGE, type = 'website', noindex = false }) {
  const { pathname } = useLocation();
  const routeMeta = getRouteMeta(pathname);
  const pageTitle = title || routeMeta.title;
  const pageDescription = description || routeMeta.description;
  const canonicalPath = pathname === '/' ? '' : pathname;
  const canonicalUrl = `${SITE_URL}${canonicalPath}`;
  const shouldNoindex = noindex || AUTH_PATHS.includes(pathname) || PRIVATE_PREFIXES.some((prefix) => pathname.startsWith(prefix));

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'BookMyStyle',
    url: SITE_URL,
    potentialAction: {
      '@type': 'SearchAction',
      target: `${SITE_URL}/salons?search={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  };

  return (
    <Helmet>
      <html lang="en" />
      <title>{pageTitle}</title>
      <meta name="description" content={pageDescription} />
      <meta name="robots" content={shouldNoindex ? 'noindex,nofollow' : 'index,follow'} />
      <link rel="canonical" href={canonicalUrl} />

      <meta property="og:site_name" content="BookMyStyle" />
      <meta property="og:type" content={type} />
      <meta property="og:url" content={canonicalUrl} />
      <meta property="og:title" content={pageTitle} />
      <meta property="og:description" content={pageDescription} />
      <meta property="og:image" content={image} />

      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:url" content={canonicalUrl} />
      <meta name="twitter:title" content={pageTitle} />
      <meta name="twitter:description" content={pageDescription} />
      <meta name="twitter:image" content={image} />

      <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
    </Helmet>
  );
}
