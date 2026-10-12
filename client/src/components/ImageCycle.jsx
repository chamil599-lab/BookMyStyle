import { useState, useEffect, useCallback } from 'react';
import api, { asArray } from '../utils/api';

const HeroImageCarousel = () => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);
  const [direction, setDirection] = useState('next');
  const [isPaused, setIsPaused] = useState(false);

  const initialImages = [
    {
      src: "https://www.pngall.com/wp-content/uploads/5/Hair-Salon-PNG-Free-Image.png",
      alt: "Hair Styling",
      title: "Expert Hair Styling",
      description: "Transform your look with professional styling"
    },
    {
      src: "https://purepng.com/public/uploads/large/purepng.com-beautiful-girlwomenpeoplepersonsfemalebeautiful-1121525108709vpm7g.png",
      alt: "Makeup Services",
      title: "Glamorous Makeup",
      description: "Enhance your natural beauty"
    },
    {
      src: "https://www.pngkey.com/png/detail/71-710475_love-your-hair-hair-salon-girl-png.png",
      alt: "Nail Services",
      title: "Nail Artistry",
      description: "Perfect nails for every occasion"
    },
    {
      src: "https://www.pngall.com/wp-content/uploads/5/Hair-Salon-PNG-Free-Image.png",
      alt: "Spa Treatment",
      title: "Relaxing Spa",
      description: "Indulge in ultimate relaxation"
    },
    {
      src: "https://purepng.com/public/uploads/large/purepng.com-beautiful-girlwomenpeoplepersonsfemalebeautiful-1121525108709vpm7g.png",
      alt: "Professional Styling",
      title: "Professional Care",
      description: "Excellence in every service"
    }
  ];

  const [images, setImages] = useState(initialImages);

  // Load images from backend; fall back to initialImages if fetch fails
  useEffect(() => {
    api.get('/image-cycles')
      .then((res) => {
        const data = asArray(res.data);
        if (data.length === 0) return;
        const mapped = data.map((it) => ({
          src: it.imageUrl || it.srcUrl || null,
          alt: it.alt || '',
          title: it.title || '',
          description: it.description || '',
        })).filter(i => i.src);

        if (mapped.length) setImages(mapped);
      })
      .catch((err) => {
        console.error('ImageCycle fetch failed, using fallback images:', err);
      });
  }, []);

  // Custom Icons as SVG components
  const ChevronLeft = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="15 18 9 12 15 6"></polyline>
    </svg>
  );

  const ChevronRight = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="9 18 15 12 9 6"></polyline>
    </svg>
  );

  const Sparkles = ({ className }) => (
    <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 3v18M3 12h18"></path>
      <path d="M6.6 6.6l10.8 10.8M17.4 6.6L6.6 17.4"></path>
    </svg>
  );

  const handleNext = useCallback(() => {
    if (isAnimating) return;
    setDirection('next');
    setIsAnimating(true);
    setTimeout(() => {
      setCurrentIndex((prev) => (prev + 1) % images.length);
      setIsAnimating(false);
    }, 600);
  }, [isAnimating, images.length]);

  const handlePrev = useCallback(() => {
    if (isAnimating) return;
    setDirection('prev');
    setIsAnimating(true);
    setTimeout(() => {
      setCurrentIndex((prev) => (prev - 1 + images.length) % images.length);
      setIsAnimating(false);
    }, 600);
  }, [isAnimating, images.length]);

  useEffect(() => {
    if (isPaused) return;

    const interval = setInterval(() => {
      handleNext();
    }, 5000);

    return () => clearInterval(interval);
  }, [isPaused, handleNext]);

  // goToSlide is intentionally omitted (dots indicator is commented out)

  return (
    <div 
      className="relative order-2 lg:order-2"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      <div className="relative group">
        {/* Animated Background Blobs */}
        <div className="absolute inset-0 -z-10">
          <div className="absolute top-0 right-0 rounded-full bg-yellow-300 w-72 h-72 mix-blend-multiply filter blur-3xl opacity-[0.16] animate-blob"></div>
          <div className="absolute bottom-0 left-0 rounded-full bg-amber-300 w-72 h-72 mix-blend-multiply filter blur-3xl opacity-[0.16] animate-blob animation-delay-2000"></div>
          <div className="absolute rounded-full bg-yellow-400 top-1/2 left-1/2 w-72 h-72 mix-blend-multiply filter blur-3xl opacity-[0.16] animate-blob animation-delay-4000"></div>
        </div>

        {/* Decorative Background Shapes */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute w-40 h-40 border-4 rounded-full -top-8 -right-8 border-yellow-200/30"></div>
          <div className="absolute w-32 h-32 border-4 rounded-full -bottom-8 -left-8 border-amber-200/30"></div>
          <Sparkles className="absolute w-6 h-6 top-10 right-10 text-yellow-400/40 animate-pulse" />
          <Sparkles className="absolute w-5 h-5 bottom-10 left-10 text-amber-400/40 animate-pulse animation-delay-1000" />
        </div>

        {/* Layered Background Cards */}
        <div className="absolute inset-4 bg-linear-to-br from-yellow-200 via-amber-100 to-yellow-100 rounded-[3rem] transform rotate-6 opacity-35
                      transition-transform duration-500 group-hover:rotate-12 shadow-2xl"></div>
        <div className="absolute inset-2 bg-linear-to-br from-yellow-100 via-white to-amber-50 rounded-[3rem] transform rotate-3
                      transition-transform duration-500 group-hover:rotate-6 shadow-xl"></div>
        
        {/* Main Image Container */}
        <div className="relative p-8 border shadow-2xl bg-linear-to-br from-slate-900 via-slate-800 to-slate-900 rounded-[2.5rem] backdrop-blur-xs border-yellow-200/20 overflow-hidden">
          
          {/* Shimmer Effect */}
          <div className="absolute inset-0 transition-transform duration-1000 -translate-x-full pointer-events-none group-hover:translate-x-full bg-linear-to-r from-transparent via-white/30 to-transparent"></div>
          
          {/* Image Display Area */}
          <div className="relative h-[450px] flex items-center justify-center overflow-hidden rounded-3xl">
            {images.map((image, index) => {
              const isActive = index === currentIndex;
              const isPrev = index === (currentIndex - 1 + images.length) % images.length;
              const isNext = index === (currentIndex + 1) % images.length;
              
              return (
                <div
                  key={index}
                  className={`absolute w-full h-full transition-all duration-700 ease-out
                    ${isActive 
                      ? 'opacity-100 scale-100 translate-x-0 z-20' 
                      : isPrev && direction === 'prev'
                      ? 'opacity-0 scale-90 -translate-x-full z-10'
                      : isNext && direction === 'next'
                      ? 'opacity-0 scale-90 translate-x-full z-10'
                      : 'opacity-0 scale-90 translate-y-8 z-0'
                    }
                    ${isAnimating && isActive 
                      ? direction === 'next' 
                        ? 'opacity-0 -translate-x-full scale-90' 
                        : 'opacity-0 translate-x-full scale-90'
                      : ''
                    }
                  `}
                >
                  <img
                    src={image.src}
                    alt={image.alt}
                    className="object-contain object-center w-full h-full transition-transform duration-700 transform hover:scale-105"
                    style={{ 
                      filter: 'drop-shadow(0 25px 50px rgba(250, 204, 21, 0.18))',
                    }}
                  />
                </div>
              );
            })}
            
            {/* Bottom Gradient Fade - Blends bottom 5% of image with background */}
            <div 
              className="absolute bottom-0 left-0 right-0 z-30 pointer-events-none"
              style={{
                height: '5%',
                background: 'linear-gradient(to top, rgba(251, 191, 36, 0.18) 0%, rgba(251, 191, 36, 0.08) 30%, transparent 100%)'
              }}
            ></div>
          </div>

          {/* Service Info Overlay - Now with z-50 to be in front of images */}
          <div className="absolute bottom-12 left-1/2 -translate-x-1/2 w-[90%] bg-slate-900/75 backdrop-blur-md 
                        rounded-2xl p-6 shadow-xl border border-yellow-200/20 
                        transition-all duration-500 transform hover:scale-105 z-50">
            <div className="text-center">
              <h3 className="mb-2 text-2xl font-bold text-transparent bg-linear-to-r from-yellow-300 to-amber-300 bg-clip-text">
                {images[currentIndex].title}
              </h3>
              <p className="text-sm font-medium text-slate-300">
                {images[currentIndex].description}
              </p>
            </div>
          </div>

          {/* Navigation Arrows */}
          <button
            onClick={handlePrev}
            disabled={isAnimating}
            className="absolute z-50 flex items-center justify-center w-12 h-12 transition-all duration-300 -translate-y-1/2 border rounded-full shadow-lg border-yellow-200/30 left-4 top-1/2 bg-slate-900/90 hover:bg-slate-800 backdrop-blur-md hover:shadow-xl hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed group/btn"
            aria-label="Previous slide"
          >
            <div className="text-yellow-300 transition-colors group-hover/btn:text-yellow-200">
              <ChevronLeft />
            </div>
          </button>

          <button
            onClick={handleNext}
            disabled={isAnimating}
            className="absolute z-50 flex items-center justify-center w-12 h-12 transition-all duration-300 -translate-y-1/2 border rounded-full shadow-lg border-yellow-200/30 right-4 top-1/2 bg-slate-900/90 hover:bg-slate-800 backdrop-blur-md hover:shadow-xl hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed group/btn"
            aria-label="Next slide"
          >
            <div className="text-yellow-300 transition-colors group-hover/btn:text-yellow-200">
              <ChevronRight />
            </div>
          </button>

          {/* Gradient Overlays */}
          <div className="absolute inset-0 pointer-events-none bg-linear-to-t from-yellow-900/8 via-transparent to-transparent rounded-3xl"></div>
          <div className="absolute inset-0 pointer-events-none bg-linear-to-b from-white/10 via-transparent to-transparent rounded-3xl"></div>
        </div>

        {/* Enhanced Dots Indicator 
        <div className="absolute z-50 flex items-center gap-2 px-4 py-2 transform -translate-x-1/2 border rounded-full shadow-lg bottom-0 left-1/2 bg-white/80 backdrop-blur-md border-green-100/50">
          {images.map((_, index) => (
            <button
              key={index}
              onClick={() => goToSlide(index)}
              disabled={isAnimating}
              className={`transition-all duration-500 rounded-full
                ${index === currentIndex 
                  ? 'bg-linear-to-r from-green-600 to-green-500 w-8 h-2.5 shadow-md'
                  : 'bg-green-200 hover:bg-green-300 w-2.5 h-2.5 hover:w-4'
                }
                disabled:cursor-not-allowed
              `}
              aria-label={`Go to slide ${index + 1}`}
            />
          ))}
        </div> */}

        {/* Pause Indicator */}
        {isPaused && (
          <div className="absolute top-4 right-4 bg-slate-900/90 backdrop-blur-md px-3 py-1.5 
                        rounded-full text-xs font-medium text-yellow-300 shadow-md border border-yellow-200/30
                        animate-fade-in z-50">
            Paused
          </div>
        )}
      </div>

      <style>{`
        @keyframes blob {
          0%, 100% { transform: translate(0, 0) scale(1); }
          25% { transform: translate(20px, -20px) scale(1.1); }
          50% { transform: translate(-20px, 20px) scale(0.9); }
          75% { transform: translate(20px, 10px) scale(1.05); }
        }

        @keyframes fade-in {
          from { opacity: 0; transform: scale(0.9); }
          to { opacity: 1; transform: scale(1); }
        }

        .animate-blob {
          animation: blob 7s infinite;
        }

        .animation-delay-2000 {
          animation-delay: 2s;
        }

        .animation-delay-4000 {
          animation-delay: 4s;
        }

        .animation-delay-1000 {
          animation-delay: 1s;
        }

        .animate-fade-in {
          animation: fade-in 0.3s ease-out;
        }
      `}</style>
    </div>
  );
};

export default HeroImageCarousel;
