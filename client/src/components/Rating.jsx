
const Rating = ({ rating, showBadge = true }) => {
  if (!rating) return null;

  return (
    <div className="flex items-center gap-1 mb-3">
      {/* Stars */}
      {[1, 2, 3, 4, 5].map((star) => {
        const isFull = rating >= star;
        const isHalf = rating >= star - 0.5 && rating < star;

        return (
          <span key={star} className="text-green-500 text-lg drop-shadow-sm">
            {isFull ? (
              <i className="fas fa-star"></i>
            ) : isHalf ? (
              <i className="fas fa-star-half-alt"></i>
            ) : (
              <i className="far fa-star text-gray-300"></i>
            )}
          </span>
        );
      })}

      {/* Numeric Rating */}
      <span className="text-sm text-gray-700 font-medium ml-1">
        {rating.toFixed(1)}
      </span>

      {/* Optional "Top Rated" badge */}
      {showBadge && rating >= 4.5 && (
        <span className="ml-2 bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-semibold">
          Top Rated
        </span>
      )}
    </div>
  );
};

export default Rating;
