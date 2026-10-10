import { useState, useEffect } from "react";
import api from "../utils/api";
import { useAuth } from "../context/AuthContext";
import { Link } from "react-router-dom";

const StarIcon = ({ filled }) => (
  <svg
    className={`w-8 h-8 transition-colors ${filled ? "text-[var(--terra)]" : "text-[var(--cream)]"}`}
    viewBox="0 0 24 24"
    fill="currentColor"
  >
    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
  </svg>
);

const RATING_LABELS = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

const RatingForm = ({ salonId, onSubmitted }) => {
  const { user } = useAuth();
  const email = user?.email || "";
  const [rating, setRating] = useState(0);
  const [hoverRating, setHoverRating] = useState(0);
  const [review, setReview] = useState("");
  const [loading, setLoading] = useState(false);
  const [checkingEligibility, setCheckingEligibility] = useState(false);
  const [canRate, setCanRate] = useState(null);
  const [eligibilityMessage, setEligibilityMessage] = useState("");
  const [message, setMessage] = useState(null);
  const [error, setError] = useState(null);

  const checkEligibility = async () => {
    if (!user) {
      setCanRate(null);
      setEligibilityMessage("");
      return;
    }
    setCheckingEligibility(true);
    try {
      const response = await api.get(`/salons/${salonId}/can-rate`);
      setCanRate(response.data.can_rate);
      setEligibilityMessage(response.data.message);
      if (!response.data.can_rate) setError(response.data.message);
      else setError(null);
    } catch {
      setCanRate(false);
      setEligibilityMessage("Unable to verify eligibility. Please try again.");
    } finally {
      setCheckingEligibility(false);
    }
  };

  useEffect(() => {
    setCanRate(null);
    setError(null);
    checkEligibility();
  }, [salonId, user?.id, user?.email]);

  const submit = async (e) => {
    e?.preventDefault();
    setMessage(null);
    setError(null);
    if (!email || rating < 1) {
      setError("Please provide an email and select a rating (1–5).");
      return;
    }
    if (canRate === null) { await checkEligibility(email); return; }
    if (canRate === false) { setError("You must complete a booking at this salon before rating."); return; }

    setLoading(true);
    try {
      await api.post(`/salons/${salonId}/ratings`, { rating, review: review || null });
      setMessage("Your review has been submitted successfully!");
      setRating(0); setReview(""); setCanRate(false); setEligibilityMessage("You have already rated this salon.");
      if (onSubmitted) onSubmitted();
    } catch (err) {
      if (err.response?.status === 403) setError("You must complete a booking at this salon before rating.");
      else if (err.response?.status === 409) setError("You have already rated this salon.");
      else {
        const errors = err.response?.data?.errors;
        setError(errors ? Object.values(errors).flat().join(", ") : err.response?.data?.error || "Failed to submit rating.");
      }
    } finally {
      setLoading(false);
    }
  };

  const reset = () => {
    setRating(0); setReview("");
    setError(null); setMessage(null);
    checkEligibility();
  };

  const activeRating = hoverRating || rating;

  if (!user) {
    return <p className="text-[var(--ink)]">Sign in to review a salon you have booked. <Link to="/login" className="underline">Sign in</Link></p>;
  }

  return (
    <form onSubmit={submit} className="space-y-8">

      {/* Success banner */}
      {message && (
        <div className="flex items-start gap-4 border-l-4 border-green-500 bg-green-50 px-6 py-5">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-green-500 text-white">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.25em] text-green-700 uppercase mb-1">
              Review Submitted
            </div>
            <p className="text-[14px] text-[var(--ink)]">{message}</p>
          </div>
        </div>
      )}

      {/* Error banner */}
      {error && (
        <div className="flex items-start gap-4 border-l-4 border-[var(--sienna)] bg-[var(--cream)] px-6 py-5">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-[var(--sienna)] text-white">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <div>
            <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.25em] text-[var(--sienna)] uppercase mb-1">
              Notice
            </div>
            <p className="text-[14px] text-[var(--ink)]">{error}</p>
          </div>
        </div>
      )}

      {/* Email */}
      <div>
        <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
          Email Address <span className="opacity-60">*</span>
        </label>
        <input
          type="email"
          placeholder="your@email.com"
          value={email}
          readOnly
          className="w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition"
        />

        {/* Eligibility feedback */}
        {checkingEligibility && (
          <div className="mt-2 flex items-center gap-2 text-[13px] text-[var(--muted)]">
            <svg className="animate-spin h-4 w-4 text-[var(--terra)]" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Checking eligibility…
          </div>
        )}
        {canRate === true && !checkingEligibility && (
          <div className="mt-2 flex items-center gap-2 text-[13px] text-green-600 font-medium">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {eligibilityMessage}
          </div>
        )}
        {canRate === false && !checkingEligibility && (
          <div className="mt-2 flex items-center gap-2 text-[13px] text-[var(--sienna)] font-medium">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {eligibilityMessage}
          </div>
        )}
      </div>

      {/* Star Rating */}
      <div>
        <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
          Your Rating <span className="opacity-60">*</span>
        </label>
        <div className="border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-5">
          <div className="flex items-center gap-1 mb-3">
            {[1, 2, 3, 4, 5].map((n) => (
              <button
                type="button"
                key={n}
                onClick={() => setRating(n)}
                onMouseEnter={() => setHoverRating(n)}
                onMouseLeave={() => setHoverRating(0)}
                disabled={canRate === false}
                className={`transition-transform hover:scale-110 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed`}
                aria-label={`Rate ${n} stars`}
              >
                <StarIcon filled={activeRating >= n} />
              </button>
            ))}
            <div className="ml-3 flex flex-col">
              <span className="font-[Unbounded] text-[13px] font-bold text-[var(--ink)]">
                {activeRating ? RATING_LABELS[activeRating] : 'Select a rating'}
              </span>
              {activeRating > 0 && (
                <span className="text-[11px] text-[var(--muted)]">{activeRating} / 5 stars</span>
              )}
            </div>
          </div>
          {/* Star fill bar */}
          <div className="flex gap-1 h-1">
            {[1, 2, 3, 4, 5].map((n) => (
              <div
                key={n}
                className={`flex-1 transition-colors ${activeRating >= n ? 'bg-[var(--terra)]' : 'bg-[var(--cream)]'}`}
              />
            ))}
          </div>
        </div>
      </div>

      {/* Review Text */}
      <div>
        <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
          Your Review <span className="font-normal opacity-50 normal-case tracking-normal">(Optional)</span>
        </label>
        <textarea
          placeholder="Share your experience with this salon…"
          value={review}
          onChange={(e) => setReview(e.target.value)}
          disabled={canRate === false}
          rows={4}
          className={`w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition resize-none ${canRate === false ? 'opacity-40 cursor-not-allowed' : ''}`}
        />
      </div>

      {/* Info notice */}
      <div className="flex items-start gap-4 border-l-4 border-[var(--terra)] bg-[var(--cream)] px-5 py-4">
        <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-[var(--terra)] text-white">
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p className="text-[13px] text-[var(--ink)] leading-relaxed">
          Only customers who have <strong>completed a booking</strong> at this salon can submit a rating.
        </p>
      </div>

      {/* Actions */}
      <div className="flex items-center gap-3 border-t-2 border-[var(--cream)] pt-6">
        <button
          type="submit"
          disabled={loading || canRate === false || checkingEligibility}
          className="flex-1 inline-flex items-center justify-center gap-3 bg-[var(--terra)] py-4 px-6 font-[Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-[var(--sienna)] hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
        >
          {loading ? (
            <>
              <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Submitting…
            </>
          ) : (
            <>
              Submit Review
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
              </svg>
            </>
          )}
        </button>

        <button
          type="button"
          onClick={reset}
          className="border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 font-[Unbounded] text-[11px] font-semibold tracking-[0.15em] text-[var(--muted)] uppercase transition hover:border-[var(--terra)] hover:text-[var(--terra)]"
        >
          Reset
        </button>
      </div>
    </form>
  );
};

export default RatingForm;
