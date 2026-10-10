import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Helmet } from 'react-helmet-async';
import GoogleAuthButton from '../components/GoogleAuthButton';

const EyeIcon = ({ open }) => open ? (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
  </svg>
) : (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
  </svg>
);

const inp = 'w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition';
const lbl = 'mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase';

export default function RegisterPage() {
  const navigate = useNavigate();
  const { register } = useAuth();

  const [step, setStep]       = useState(1);
  const [role, setRole]       = useState('');
  const [success, setSuccess] = useState(false);

  const [form, setForm] = useState({
    name: '', email: '', password: '', password_confirmation: '',
    phone: '', location: '', acceptTerms: false,
  });
  const [error, setError]       = useState('');
  const [loading, setLoading]   = useState(false);
  const [showPw, setShowPw]     = useState(false);
  const [showCfm, setShowCfm]   = useState(false);

  const set = (e) => {
    const val = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    setForm(f => ({ ...f, [e.target.name]: val }));
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    if (form.password !== form.password_confirmation) return setError('Passwords do not match');
    if (form.password.length < 8) return setError('Password must be at least 8 characters');
    if (!form.acceptTerms) return setError('You must accept the terms and conditions');

    setLoading(true);
    const payload = {
      name: form.name, email: form.email,
      password: form.password, password_confirmation: form.password_confirmation,
      role,
      ...(role === 'owner' && { phone: form.phone, location: form.location }),
    };
    const result = await register(payload);
    setLoading(false);

    if (!result.success) return setError(result.error);

    if (role === 'owner') {
      if (result.user?.is_approved) {
        navigate('/owner');
      } else {
        setSuccess(true);
      }
    } else {
      navigate('/dashboard');
    }
  };

  /* ── Left panel ── */
  const LeftPanel = () => (
    <div
      className="hidden lg:flex lg:w-1/2 xl:w-[55%] relative flex-col overflow-hidden"
      style={{ backgroundImage: 'url(https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1400&q=85)', backgroundSize: 'cover', backgroundPosition: 'center' }}
    >
      <div className="absolute inset-0 bg-gradient-to-br from-[rgba(30,23,16,0.90)] via-[rgba(30,23,16,0.72)] to-[rgba(196,98,45,0.40)]" />
      <div className="absolute bottom-0 left-0 right-0 h-1 bg-[var(--terra)]" />
      <div className="relative z-10 flex flex-col h-full px-12 py-12">
        <Link to="/" className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center">
            <img src="/logo2.png" alt="BookMyStyle" className="h-10 w-10 object-contain" />
          </div>
          <div>
            <p className="font-[Unbounded] text-xs font-bold tracking-[0.25em] text-white uppercase">Book My Style</p>
            <p className="text-[10px] text-white/50 tracking-wide">Beauty Booking</p>
          </div>
        </Link>
        <div className="flex flex-col justify-center flex-1">
          <div className="mb-4 inline-flex items-center gap-3 font-[Unbounded] text-[9px] font-semibold tracking-[0.3em] text-[var(--terra2)] uppercase">
            <span className="h-0.5 w-5 bg-[var(--terra2)]" />
            Get Started
          </div>
          <h1 className="font-[Unbounded] text-5xl xl:text-6xl font-extrabold leading-[0.95] tracking-tight text-white mb-6">
            Your style<br />
            <em className="font-[Libre_Baskerville] text-[var(--terra2)]">journey begins.</em>
          </h1>
          <p className="max-w-sm text-[15px] leading-relaxed text-white/60">
            {role === 'owner'
              ? 'List your salon, manage bookings, and grow your business — all from one dashboard.'
              : 'Discover verified salons, book appointments instantly, and manage your beauty routine.'}
          </p>
          <div className="flex items-center gap-8 mt-10">
            {[['50+', 'Salons'], ['4.8★', 'Avg Rating'], ['100%', 'Verified']].map(([val, label]) => (
              <div key={label}>
                <div className="font-[Unbounded] text-2xl font-black text-[var(--terra2)]">{val}</div>
                <div className="text-[10px] font-semibold tracking-[0.15em] text-white/50 uppercase mt-0.5">{label}</div>
              </div>
            ))}
          </div>
        </div>
        <p className="text-[11px] text-white/30 tracking-wide">© {new Date().getFullYear()} BookMyStyle · All rights reserved</p>
      </div>
    </div>
  );

  /* ── Success screen (owner only) ── */
  if (success) {
    return (
      <>
        <Helmet><title>Account Submitted — BookMyStyle</title></Helmet>
        <div className="min-h-screen flex bg-[var(--sand)]">
          <LeftPanel />
          <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-20 bg-[var(--white)]">
            <div className="w-full max-w-md mx-auto text-center">
              <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center bg-[var(--terra)]">
                <svg className="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div className="mb-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase">Application Submitted</div>
              <h2 className="font-[Libre_Baskerville] text-3xl italic text-[var(--ink)] mb-4">You're on the list!</h2>
              <p className="text-[14px] text-[var(--muted)] leading-relaxed mb-8">
                Your salon owner account is pending admin review. You'll be able to log in once your account has been approved.
              </p>
              <Link to="/login"
                className="w-full inline-flex items-center justify-center gap-3 bg-[var(--terra)] py-5 px-6 font-[Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-[var(--sienna)]">
                Go to Login
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
                </svg>
              </Link>
            </div>
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <Helmet><title>Create Account — BookMyStyle</title></Helmet>
      <div className="min-h-screen flex bg-[var(--sand)]">
        <LeftPanel />

        <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-20 bg-[var(--white)]">

          {/* Mobile brand */}
          <Link to="/" className="flex items-center gap-3 mb-10 lg:hidden">
            <div className="flex h-9 w-9 items-center justify-center">
              <img src="/logo2.png" alt="BookMyStyle" className="h-9 w-9 object-contain" />
            </div>
            <p className="font-[Unbounded] text-xs font-bold tracking-[0.2em] text-[var(--ink)] uppercase">Book My Style</p>
          </Link>

          <div className="w-full max-w-md mx-auto">

            {/* Step indicator */}
            <div className="flex items-center gap-2 mb-10">
              {[1, 2].map(n => (
                <div key={n} className="flex items-center gap-2">
                  <div className={`flex h-7 w-7 items-center justify-center font-[Unbounded] text-[10px] font-bold transition-colors ${
                    step >= n ? 'bg-[var(--terra)] text-white' : 'bg-[var(--cream)] text-[var(--muted)]'
                  }`}>{n}</div>
                  {n < 2 && <div className={`w-8 h-0.5 transition-colors ${step >= 2 ? 'bg-[var(--terra)]' : 'bg-[var(--cream)]'}`} />}
                </div>
              ))}
              <span className="ml-3 font-[Unbounded] text-[9px] font-semibold tracking-[0.2em] text-[var(--muted)] uppercase">
                {step === 1 ? 'Choose role' : 'Your details'}
              </span>
            </div>

            {/* ── Step 1: Role selector ── */}
            {step === 1 && (
              <div>
                <div className="mb-8">
                  <div className="mb-3 inline-flex items-center gap-3 font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase">
                    <span className="h-0.5 w-5 bg-[var(--terra)]" />
                    Create Account
                  </div>
                  <h2 className="font-[Libre_Baskerville] text-3xl italic text-[var(--ink)] mb-2">Who are you?</h2>
                  <p className="text-[14px] text-[var(--muted)]">Choose how you'd like to join BookMyStyle.</p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                  {[
                    { value: 'customer', icon: 'person', title: "I'm a Customer", desc: 'Discover & book salon appointments' },
                    { value: 'owner',    icon: 'store',  title: "I'm a Salon Owner", desc: 'List and manage your salon' },
                  ].map(opt => (
                    <button
                      key={opt.value}
                      type="button"
                      onClick={() => setRole(opt.value)}
                      className={`text-left p-6 border-2 transition-all ${
                        role === opt.value
                          ? 'border-[var(--terra)] bg-[var(--cream)]'
                          : 'border-[var(--cream)] bg-[var(--sand)] hover:border-[var(--terra)]/50'
                      }`}
                    >
                      <span className={`material-symbols-outlined text-3xl mb-3 block ${role === opt.value ? 'text-[var(--terra)]' : 'text-[var(--muted)]'}`}>
                        {opt.icon}
                      </span>
                      <p className={`font-[Unbounded] text-[12px] font-bold tracking-wide mb-1 ${role === opt.value ? 'text-[var(--terra)]' : 'text-[var(--ink)]'}`}>
                        {opt.title}
                      </p>
                      <p className="text-[12px] text-[var(--muted)]">{opt.desc}</p>
                    </button>
                  ))}
                </div>

                <button
                  type="button"
                  disabled={!role}
                  onClick={() => setStep(2)}
                  className="w-full inline-flex items-center justify-center gap-3 bg-[var(--terra)] py-5 px-6 font-[Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-[var(--sienna)] hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                >
                  Continue
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
                  </svg>
                </button>

                <div className="flex items-center gap-4 my-8">
                  <div className="flex-1 h-px bg-[var(--cream)]" />
                  <span className="font-[Unbounded] text-[9px] font-semibold tracking-[0.3em] text-[var(--muted)] uppercase">Have an account?</span>
                  <div className="flex-1 h-px bg-[var(--cream)]" />
                </div>
                <Link to="/login"
                  className="w-full inline-flex items-center justify-center gap-3 border-2 border-[var(--cream)] bg-[var(--sand)] py-4 px-6 font-[Unbounded] text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase transition hover:border-[var(--terra)] hover:text-[var(--terra)]">
                  Sign In
                </Link>
              </div>
            )}

            {/* ── Step 2: Registration form ── */}
            {step === 2 && (
              <div>
                <div className="mb-8 flex items-start justify-between gap-4">
                  <div>
                    <button type="button" onClick={() => setStep(1)}
                      className="mb-4 inline-flex items-center gap-2 font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-[var(--muted)] uppercase hover:text-[var(--terra)] transition">
                      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 12H5m7 7-7-7 7-7" />
                      </svg>
                      Back
                    </button>
                    <h2 className="font-[Libre_Baskerville] text-3xl italic text-[var(--ink)] mb-2">Your Details</h2>
                    <p className="text-[14px] text-[var(--muted)]">Fill in your details to get started.</p>
                  </div>
                  <span className={`mt-1 shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 font-[Unbounded] text-[9px] font-bold tracking-[0.2em] uppercase ${
                    role === 'owner' ? 'bg-[var(--terra)] text-white' : 'bg-[var(--cream)] text-[var(--terra)]'
                  }`}>
                    <span className="material-symbols-outlined text-sm">{role === 'owner' ? 'store' : 'person'}</span>
                    {role === 'owner' ? 'Salon Owner' : 'Customer'}
                  </span>
                </div>

                {error && (
                  <div className="mb-6 flex items-start gap-4 border-l-4 border-[var(--sienna)] bg-[var(--cream)] px-5 py-4">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center bg-[var(--sienna)] text-white">
                      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </div>
                    <div>
                      <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.25em] text-[var(--sienna)] uppercase mb-1">Error</div>
                      <p className="text-[14px] text-[var(--ink)]">{error}</p>
                    </div>
                  </div>
                )}

                <GoogleAuthButton role={role} onError={setError} disabled={loading} />

                <div className="flex items-center gap-4 my-6">
                  <div className="flex-1 h-px bg-[var(--cream)]" />
                  <span className="font-[Unbounded] text-[9px] font-semibold tracking-[0.3em] text-[var(--muted)] uppercase">or</span>
                  <div className="flex-1 h-px bg-[var(--cream)]" />
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label className={lbl}>Full Name <span className="opacity-60">*</span></label>
                    <input name="name" type="text" autoComplete="name" required value={form.name} onChange={set} placeholder="Jane Doe" className={inp} />
                  </div>
                  <div>
                    <label className={lbl}>Email Address <span className="opacity-60">*</span></label>
                    <input name="email" type="email" autoComplete="email" required value={form.email} onChange={set} placeholder="your@email.com" className={inp} />
                  </div>

                  {/* Owner-only fields */}
                  {role === 'owner' && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                      <div>
                        <label className={lbl}>Phone</label>
                        <input name="phone" type="tel" value={form.phone} onChange={set} placeholder="+94 77 000 0000" className={inp} />
                      </div>
                      <div>
                        <label className={lbl}>City / Location</label>
                        <input name="location" type="text" value={form.location} onChange={set} placeholder="Colombo" className={inp} />
                      </div>
                    </div>
                  )}

                  <div>
                    <label className={lbl}>Password <span className="opacity-60">*</span></label>
                    <div className="relative">
                      <input name="password" type={showPw ? 'text' : 'password'} autoComplete="new-password" required value={form.password} onChange={set} placeholder="••••••••" className={`${inp} pr-14`} />
                      <button type="button" onClick={() => setShowPw(p => !p)} className="absolute inset-y-0 right-4 flex items-center text-[var(--muted)] hover:text-[var(--terra)] transition">
                        <EyeIcon open={showPw} />
                      </button>
                    </div>
                    <p className="mt-2 text-[11px] text-[var(--muted)] tracking-wide">Must be at least 8 characters</p>
                  </div>
                  <div>
                    <label className={lbl}>Confirm Password <span className="opacity-60">*</span></label>
                    <div className="relative">
                      <input name="password_confirmation" type={showCfm ? 'text' : 'password'} autoComplete="new-password" required value={form.password_confirmation} onChange={set} placeholder="••••••••" className={`${inp} pr-14`} />
                      <button type="button" onClick={() => setShowCfm(p => !p)} className="absolute inset-y-0 right-4 flex items-center text-[var(--muted)] hover:text-[var(--terra)] transition">
                        <EyeIcon open={showCfm} />
                      </button>
                    </div>
                  </div>

                  <label className="flex items-start gap-3 cursor-pointer group">
                    <div className="relative shrink-0 mt-0.5">
                      <input name="acceptTerms" type="checkbox" checked={form.acceptTerms} onChange={set} className="sr-only peer" />
                      <div className="h-5 w-5 border-2 border-[var(--cream)] bg-[var(--sand)] peer-checked:border-[var(--terra)] peer-checked:bg-[var(--terra)] transition flex items-center justify-center">
                        {form.acceptTerms && (
                          <svg className="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                          </svg>
                        )}
                      </div>
                    </div>
                    <span className="text-[13px] text-[var(--ink)] leading-relaxed group-hover:text-[var(--terra)] transition">
                      I agree to the{' '}
                      <Link to="/terms-and-condition" target="_blank" className="text-[var(--terra)] underline underline-offset-2 hover:text-[var(--sienna)]">Terms and Conditions</Link>
                      {' '}and{' '}
                      <Link to="/privacy-policy" target="_blank" className="text-[var(--terra)] underline underline-offset-2 hover:text-[var(--sienna)]">Privacy Policy</Link>
                    </span>
                  </label>

                  <button type="submit" disabled={loading}
                    className="w-full inline-flex items-center justify-center gap-3 bg-[var(--terra)] py-5 px-6 font-[Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-[var(--sienna)] hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    {loading ? (
                      <>
                        <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Creating account…
                      </>
                    ) : (
                      <>
                        {role === 'owner' ? 'Submit Application' : 'Create Account'}
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
                        </svg>
                      </>
                    )}
                  </button>
                </form>
              </div>
            )}

          </div>
        </div>
      </div>
    </>
  );
}
