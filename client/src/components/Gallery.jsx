const Gallery = ({ images, salonName, onImageClick }) => {
  if (!images || images.length === 0) {
    return (
      <div className="text-center py-12 md:py-20 bg-[var(--sand)] border-2 border-dashed border-[var(--cream)]">
        <div className="w-16 h-16 md:w-24 md:h-24 bg-[var(--cream)] flex items-center justify-center mx-auto mb-4 md:mb-6">
          <svg className="w-8 h-8 md:w-12 md:h-12 text-[var(--muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 className="text-xl md:text-2xl font-bold text-[var(--ink)] mb-2">No Gallery Images Yet</h3>
        <p className="text-[var(--muted)] text-sm md:text-base">Check back soon for salon photos</p>
      </div>
    );
  }

  return (
    <>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        {images.map((img, idx) => (
          <div
            key={img.id || idx}
            className={`group relative aspect-square overflow-hidden border-2 border-[var(--cream)] transition-all duration-300 cursor-pointer ${
              img.type === 'cover' ? 'ring-2 ring-[var(--terra)] ring-offset-2' : ''
            }`}
            onClick={() => onImageClick(img.url)}
          >
            <img
              src={img.thumb || img.url}
              alt={`${salonName} ${img.type === 'cover' ? 'Cover' : 'Gallery'} ${idx + 1}`}
              className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              onError={(e) => {
                e.target.src = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=300&q=80';
              }}
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              {img.type === 'cover' && (
                <div className="absolute top-3 left-3 bg-[var(--terra)] text-white text-xs font-bold px-3 py-1 rounded-full">
                  Cover
                </div>
              )}
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="bg-white/20 backdrop-blur-sm rounded-full p-3">
                  <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-6 text-center">
        <p className="text-[var(--muted)] text-sm">
          {images.length} {images.length === 1 ? 'image' : 'images'} • Click to enlarge
        </p>
      </div>
    </>
  );
};

export default Gallery;
