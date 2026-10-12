import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import api from '../utils/api';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await api.post('/auth/forgot-password', { email });
      if (response.data.success) {
        setSuccess('Password reset link sent! Check your email.');
        setEmail('');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send reset link');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Helmet>
        <title>Forgot Password — BookMyStyle</title>
      </Helmet>

      <div className="min-h-screen flex bg-(--sand)">

        {/* ── Left panel ── */}
        <div
          className="hidden lg:flex lg:w-1/2 xl:w-[55%] relative flex-col overflow-hidden"
          style={{
            backgroundImage: 'url(https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=1400&q=85)',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
          }}
        >
          <div className="absolute inset-0 bg-linear-to-br from-[rgba(30,23,16,0.90)] via-[rgba(30,23,16,0.72)] to-[rgba(196,98,45,0.40)]" />
          <div className="absolute bottom-0 left-0 right-0 h-1 bg-(--terra)" />

          <div className="relative z-10 flex flex-col h-full px-12 py-12">

            {/* Brand */}
            <Link to="/" className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center">
                <img src="/logo2.png" alt="BookMyStyle" className="h-10 w-10 object-contain" />
              </div>
              <div>
                <p className="font-[family-name:Unbounded] text-xs font-bold tracking-[0.25em] text-white uppercase">Book My Style</p>
                <p className="text-[10px] text-white/50 tracking-wide">Beauty Booking</p>
              </div>
            </Link>

            {/* Centre message */}
            <div className="flex flex-col justify-center flex-1">
              <div className="mb-4 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[9px] font-semibold tracking-[0.3em] text-(--terra2) uppercase">
                <span className="h-0.5 w-5 bg-(--terra2)" />
                Account Recovery
              </div>
              <h1 className="font-[family-name:Unbounded] text-5xl xl:text-6xl font-extrabold leading-[0.95] tracking-tight text-white mb-6">
                Forgot your
                <br />
                <em className="font-[family-name:Libre_Baskerville] text-(--terra2)">password?</em>
              </h1>
              <p className="max-w-sm text-[15px] leading-relaxed text-white/60">
                No worries — it happens. Enter your email and we'll send you a secure link to get back into your account.
              </p>

              {/* Stats row */}
              <div className="flex items-center gap-8 mt-10">
                <div>
                  <div className="font-[family-name:Unbounded] text-2xl font-black text-(--terra2)">50+</div>
                  <div className="text-[10px] font-semibold tracking-[0.15em] text-white/50 uppercase mt-0.5">Salons</div>
                </div>
                <div className="w-px h-8 bg-white/15" />
                <div>
                  <div className="font-[family-name:Unbounded] text-2xl font-black text-(--terra2)">4.8★</div>
                  <div className="text-[10px] font-semibold tracking-[0.15em] text-white/50 uppercase mt-0.5">Avg Rating</div>
                </div>
                <div className="w-px h-8 bg-white/15" />
                <div>
                  <div className="font-[family-name:Unbounded] text-2xl font-black text-(--terra2)">100%</div>
                  <div className="text-[10px] font-semibold tracking-[0.15em] text-white/50 uppercase mt-0.5">Verified</div>
                </div>
              </div>
            </div>

            <p className="text-[11px] text-white/30 tracking-wide">
              © {new Date().getFullYear()} BookMyStyle · All rights reserved
            </p>
          </div>
        </div>

        {/* ── Right panel ── */}
        <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-20 bg-(--white)">

          {/* Mobile brand */}
          <Link to="/" className="flex items-center gap-3 mb-10 lg:hidden">
            <div className="flex h-9 w-9 items-center justify-center">
              <img src="/logo2.png" alt="BookMyStyle" className="h-9 w-9 object-contain" />
            </div>
            <p className="font-[family-name:Unbounded] text-xs font-bold tracking-[0.2em] text-(--ink) uppercase">Book My Style</p>
          </Link>

          <div className="w-full max-w-md mx-auto">

            {/* Heading */}
            <div className="mb-10">
              <div className="mb-3 inline-flex items-center gap-3 font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.3em] text-(--terra) uppercase">
                <span className="h-0.5 w-5 bg-(--terra)" />
                Account Recovery
              </div>
              <h2 className="font-[family-name:Libre_Baskerville] text-3xl italic text-(--ink) mb-2">
                Forgot your password?
              </h2>
              <p className="text-[14px] text-(--muted) leading-relaxed">
                Enter your email address and we'll send you a reset link.
              </p>
            </div>

            {/* Error banner */}
            {error && (
              <div className="mb-8 flex items-start gap-4 border-l-4 border-(--sienna) bg-(--cream) px-5 py-4">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-(--sienna) text-white">
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </div>
                <div>
                  <div className="font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.25em] text-(--sienna) uppercase mb-1">
                    Request Failed
                  </div>
                  <p className="text-[14px] text-(--ink)">{error}</p>
                </div>
              </div>
            )}

            {/* Success banner */}
            {success && (
              <div className="mb-8 flex items-start gap-4 border-l-4 border-(--terra) bg-(--cream) px-5 py-4">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-(--terra) text-white">
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div>
                  <div className="font-[family-name:Unbounded] text-[10px] font-semibold tracking-[0.25em] text-(--terra) uppercase mb-1">
                    Email Sent
                  </div>
                  <p className="text-[14px] text-(--ink)">{success}</p>
                </div>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">

              {/* Email */}
              <div>
                <label htmlFor="email" className="mb-2.5 block font-[family-name:Unbounded] text-[12px] font-semibold tracking-[0.2em] text-(--terra) uppercase">
                  Email Address <span className="opacity-60">*</span>
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  required
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); setError(''); }}
                  placeholder="your@email.com"
                  className="w-full border-2 border-(--cream) bg-(--sand) px-5 py-4 text-[15px] text-(--ink) placeholder:text-(--muted) outline-hidden focus:border-(--terra) transition"
                />
              </div>

              {/* Submit */}
              <button
                type="submit"
                disabled={loading}
                className="w-full inline-flex items-center justify-center gap-3 bg-(--terra) py-5 px-6 font-[family-name:Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-(--sienna) hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
              >
                {loading ? (
                  <>
                    <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Sending…
                  </>
                ) : (
                  <>
                    Send Reset Link
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
                    </svg>
                  </>
                )}
              </button>
            </form>

            {/* Divider */}
            <div className="flex items-center gap-4 my-8">
              <div className="flex-1 h-px bg-(--cream)" />
              <span className="font-[family-name:Unbounded] text-[9px] font-semibold tracking-[0.3em] text-(--muted) uppercase">Or</span>
              <div className="flex-1 h-px bg-(--cream)" />
            </div>

            {/* Back to login */}
            <Link
              to="/login"
              className="w-full inline-flex items-center justify-center gap-3 border-2 border-(--cream) bg-(--sand) py-4 px-6 font-[family-name:Unbounded] text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase transition hover:border-(--terra) hover:text-(--terra)"
            >
              Back to Sign In
            </Link>

            <p className="mt-6 text-center text-[13px] text-(--muted)">
              Don't have an account?{' '}
              <Link to="/register" className="text-(--terra) font-semibold hover:text-(--sienna)">
                Create one
              </Link>
            </p>

          </div>
        </div>
      </div>
    </>
  );
}
