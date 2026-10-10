import RatingForm from './RatingForm';

const ReviewSection = ({ salonId, onSubmitted }) => {
  return (
    <div className="bg-[var(--white)] border-2 border-[var(--cream)]">
      {/* Dark header — matches booking/gallery tab cards */}
      <div className="bg-[var(--ink)] px-8 py-6">
        <div className="mb-2 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase">
          <span className="h-0.5 w-5 bg-[var(--terra2)]" />
          Customer Feedback
        </div>
        <h3 className="font-[Libre_Baskerville] text-2xl italic text-white">
          Rate &amp; Review
        </h3>
        <div className="mt-3 h-[2px] w-10 bg-[var(--terra)]" />
      </div>

      <div className="p-8">
        <RatingForm salonId={salonId} onSubmitted={onSubmitted} />
      </div>
    </div>
  );
};

export default ReviewSection;
