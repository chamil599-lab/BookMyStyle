import { useState, useEffect } from 'react';
import api from '../utils/api';
import { useAuth } from '../context/AuthContext';

// ── Custom Calendar Picker ──────────────────────────────────────────────────
const MONTHS = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];
const DAY_LABELS = ['Su','Mo','Tu','We','Th','Fr','Sa'];
const SHORT_DAY_LABELS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

// ── Mobile Day Strip (horizontal scrolling next-14-days) ────────────────────
const MobileDayStrip = ({ value, onChange, minDate }) => {
  const base = new Date(minDate + 'T00:00:00');
  const days = Array.from({ length: 14 }, (_, i) => {
    const d = new Date(base);
    d.setDate(base.getDate() + i);
    return d;
  });
  const fmt = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  };

  const monthLabel = value
    ? `${MONTHS[new Date(value + 'T00:00:00').getMonth()]} ${new Date(value + 'T00:00:00').getFullYear()}`
    : `${MONTHS[base.getMonth()]} ${base.getFullYear()}`;

  return (
    <div className="w-full">
      <div className="mb-3 font-[Unbounded] text-base font-bold text-[var(--ink)]">
        {monthLabel}
      </div>
      <div className="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        {days.map((d) => {
          const ds = fmt(d);
          const selected = value === ds;
          return (
            <button
              key={ds}
              type="button"
              onClick={() => onChange(ds)}
              className={`shrink-0 flex flex-col items-center justify-center w-14 py-3 rounded-2xl border transition-all ${
                selected
                  ? 'bg-[var(--terra)] border-[var(--terra)] text-white shadow-[0_4px_12px_rgba(196,98,45,0.35)]'
                  : 'bg-white border-[var(--cream)] text-[var(--ink)]'
              }`}
            >
              <span className={`text-[10px] font-semibold tracking-wider uppercase ${selected ? 'text-white/85' : 'text-[var(--muted)]'}`}>
                {SHORT_DAY_LABELS[d.getDay()]}
              </span>
              <span className="mt-1 font-[Unbounded] text-base font-bold">
                {d.getDate()}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
};

const CalendarPicker = ({ value, onChange, minDate }) => {
  const todayStr = new Date().toISOString().split('T')[0];
  const initial = value ? new Date(value + 'T00:00:00') : new Date();

  const [viewYear, setViewYear] = useState(initial.getFullYear());
  const [viewMonth, setViewMonth] = useState(initial.getMonth());

  const prevMonth = () => {
    if (viewMonth === 0) { setViewMonth(11); setViewYear(y => y - 1); }
    else setViewMonth(m => m - 1);
  };
  const nextMonth = () => {
    if (viewMonth === 11) { setViewMonth(0); setViewYear(y => y + 1); }
    else setViewMonth(m => m + 1);
  };

  const firstDayOfWeek = new Date(viewYear, viewMonth, 1).getDay();
  const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

  const cells = [];
  for (let i = 0; i < firstDayOfWeek; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);

  const handleDay = (day) => {
    const m = String(viewMonth + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    const dateStr = `${viewYear}-${m}-${d}`;
    if (dateStr < minDate) return;
    onChange(dateStr);
  };

  return (
    <div className="w-full select-none">
      {/* Month navigation */}
      <div className="flex items-center justify-between mb-4">
        <button
          type="button"
          onClick={prevMonth}
          className="flex h-9 w-9 items-center justify-center border-2 border-[var(--cream)] text-[var(--muted)] transition hover:border-[var(--terra)] hover:text-[var(--terra)]"
        >
          ‹
        </button>
        <span className="font-[Unbounded] text-sm font-bold tracking-[0.12em] text-[var(--ink)]">
          {MONTHS[viewMonth]} {viewYear}
        </span>
        <button
          type="button"
          onClick={nextMonth}
          className="flex h-9 w-9 items-center justify-center border-2 border-[var(--cream)] text-[var(--muted)] transition hover:border-[var(--terra)] hover:text-[var(--terra)]"
        >
          ›
        </button>
      </div>

      {/* Day-of-week headers */}
      <div className="mb-2 grid grid-cols-7">
        {DAY_LABELS.map(d => (
          <div key={d} className="text-center font-[Unbounded] text-[9px] font-semibold tracking-widest text-[var(--muted)] uppercase py-1">
            {d}
          </div>
        ))}
      </div>

      {/* Date grid */}
      <div className="grid grid-cols-7 gap-1">
        {cells.map((day, i) => {
          if (!day) return <div key={i} />;
          const m = String(viewMonth + 1).padStart(2, '0');
          const d = String(day).padStart(2, '0');
          const dateStr = `${viewYear}-${m}-${d}`;
          const isSelected = value === dateStr;
          const isToday = dateStr === todayStr;
          const isDisabled = dateStr < minDate;

          return (
            <button
              key={i}
              type="button"
              onClick={() => handleDay(day)}
              disabled={isDisabled}
              className={`
                relative flex h-10 w-full items-center justify-center text-[13px] font-medium transition-all
                ${isSelected
                  ? 'bg-[var(--terra)] text-white font-bold shadow-[0_4px_12px_rgba(196,98,45,0.35)]'
                  : isToday && !isDisabled
                  ? 'border-2 border-[var(--terra)] text-[var(--terra)] font-bold'
                  : isDisabled
                  ? 'text-[var(--cream)] cursor-not-allowed'
                  : 'text-[var(--ink)] hover:bg-[var(--sand)]'
                }
              `}
            >
              {day}
              {isToday && !isSelected && (
                <span className="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-[var(--terra)]" />
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
};

// ── Main Form ───────────────────────────────────────────────────────────────
export default function BookingForm({ salon, services }) {
  const { user, isAuthenticated } = useAuth();

  const todayStr = new Date().toISOString().split('T')[0];

  const [selectedDate, setSelectedDate] = useState(todayStr);
  const [selectedTime, setSelectedTime] = useState('');
  const [selectedServiceId, setSelectedServiceId] = useState('');
  const [serviceDropdownOpen, setServiceDropdownOpen] = useState(false);
  const [selectedSlots, setSelectedSlots] = useState([]);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [timeSlots, setTimeSlots] = useState([]);
  const [bookedTimes, setBookedTimes] = useState([]);
  const [blockedSlots, setBlockedSlots] = useState([]);
  const [staffCount, setStaffCount] = useState(1);

  useEffect(() => {
    if (isAuthenticated && user) {
      setName(user.name || '');
      setEmail(user.email || '');
      setPhone(user.phone || '');
    }
  }, [isAuthenticated, user]);

  useEffect(() => {
    if (salon?.id) {
      api.get(`/salons/${salon.id}/working-hours`)
        .then((res) => {
          const { open_time, close_time, number_of_staff } = res.data;
          setTimeSlots(generateTimeSlots(open_time, close_time));
          setStaffCount(number_of_staff || 1);
        })
        .catch((err) => console.error('Error fetching working hours:', err));
    }
  }, [salon]);

  useEffect(() => {
    if (salon?.id && selectedDate) {
      api.get(`/salons/${salon.id}/booked-times?date=${selectedDate}`)
        .then((res) => setBookedTimes(res.data.booked_times || []))
        .catch(() => setBookedTimes([]));
    }
  }, [salon, selectedDate]);

  useEffect(() => {
    setBlockedSlots(getBlockedSlots());
  }, [bookedTimes, services, timeSlots, staffCount]);

  const generateTimeSlots = (start, end) => {
    const slots = [];
    let current = new Date(`1970-01-01T${start}`);
    const endTime = new Date(`1970-01-01T${end}`);
    while (current < endTime) {
      const hours = current.getHours();
      const minutes = current.getMinutes();
      const ampm = hours >= 12 ? 'PM' : 'AM';
      const display =
        ((hours % 12) || 12).toString().padStart(2, '0') + ':' +
        minutes.toString().padStart(2, '0') + ' ' + ampm;
      const value = current.toTimeString().substring(0, 5);
      slots.push({ value, display });
      current.setMinutes(current.getMinutes() + 30);
    }
    return slots;
  };

  const normalizeTime = (t) => {
    if (typeof t !== 'string') return t;
    t = t.trim().toUpperCase();
    if (t.includes('AM') || t.includes('PM')) {
      const [timePart, period] = t.split(' ');
      let [h, m] = timePart.split(':').map(Number);
      if (period === 'PM' && h < 12) h += 12;
      if (period === 'AM' && h === 12) h = 0;
      return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
    }
    const [h, m] = t.split(':').map(Number);
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
  };

  const isTimePast = (timeValue) => {
    if (selectedDate !== todayStr) return false;
    const now = new Date();
    const [h, m] = timeValue.split(':').map(Number);
    const slotTime = new Date();
    slotTime.setHours(h, m, 0, 0);
    return slotTime <= now;
  };

  const getBlockedSlots = () => {
    if (!bookedTimes || bookedTimes.length === 0) return [];
    const slotBookingCount = {};
    bookedTimes.forEach((booking) => {
      if (!booking?.time) return;
      const slotsNeeded = Math.ceil((booking.duration || 30) / 30);
      const tv = normalizeTime(booking.time);
      const [h, m] = tv.split(':').map(Number);
      const start = new Date(1970, 0, 1, h, m);
      for (let i = 0; i < slotsNeeded; i++) {
        const st = new Date(start.getTime() + i * 30 * 60000);
        const sv = st.getHours().toString().padStart(2, '0') + ':' + st.getMinutes().toString().padStart(2, '0');
        if (timeSlots.some(s => s.value === sv)) {
          slotBookingCount[sv] = (slotBookingCount[sv] || 0) + 1;
        }
      }
    });
    return Object.keys(slotBookingCount).filter(s => slotBookingCount[s] >= staffCount);
  };

  const isTimeBooked = (tv) => blockedSlots.includes(tv);
  const isSlotUnavailable = (tv) => isTimeBooked(tv) || isTimePast(tv);

  const getAvailableSlots = (tv) => {
    if (!bookedTimes) return staffCount;
    let count = 0;
    bookedTimes.forEach((booking) => {
      if (!booking?.time) return;
      const slotsNeeded = Math.ceil((booking.duration || 30) / 30);
      const norm = normalizeTime(booking.time);
      const [h, m] = norm.split(':').map(Number);
      const start = new Date(1970, 0, 1, h, m);
      for (let i = 0; i < slotsNeeded; i++) {
        const st = new Date(start.getTime() + i * 30 * 60000);
        const sv = st.getHours().toString().padStart(2, '0') + ':' + st.getMinutes().toString().padStart(2, '0');
        if (sv === tv) { count++; break; }
      }
    });
    return staffCount - count;
  };

  const handleTimeClick = (slotValue) => {
    if (!selectedServiceId) { alert('Please select a service first!'); return; }
    if (isTimePast(slotValue)) { alert('Cannot book past time slots!'); return; }
    const service = services.find(s => s.id.toString() === selectedServiceId);
    if (!service) return;
    const slotsNeeded = Math.ceil((service.duration || 30) / 30);
    const startIndex = timeSlots.findIndex(s => s.value === slotValue);
    if (startIndex === -1) { alert('Invalid time slot!'); return; }
    const newSelected = [];
    for (let i = 0; i < slotsNeeded; i++) {
      const idx = startIndex + i;
      if (idx >= timeSlots.length) { alert('Not enough time before closing!'); return; }
      if (isSlotUnavailable(timeSlots[idx].value)) { alert('Time slot unavailable!'); return; }
      newSelected.push(timeSlots[idx].value);
    }
    setSelectedTime(slotValue);
    setSelectedSlots(newSelected);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');
    if (!name || !email || !phone || !selectedServiceId || !selectedDate || !selectedTime) {
      setMessage('Please fill in all required fields.');
      setLoading(false);
      return;
    }
    if (isTimePast(selectedTime)) {
      setMessage('Cannot book past time slots. Please select a future time.');
      setLoading(false);
      return;
    }
    try {
      const response = await api.post('/bookings', {
        salon_id: salon.id, name, email, phone,
        service_id: selectedServiceId, date: selectedDate, time: selectedTime,
      });
      if (response.status === 201 || response.status === 200) {
        const bookedService = services.find(s => s.id.toString() === selectedServiceId)?.title || '';
        setMessage(`Thank you, ${name}! Your appointment for "${bookedService}" on ${selectedDate} at ${selectedTime} has been booked.`);
        setName(''); setEmail(''); setPhone('');
        setSelectedServiceId(''); setSelectedDate(todayStr);
        setSelectedTime(''); setSelectedSlots([]);
        api.get(`/salons/${salon.id}/booked-times?date=${selectedDate}`)
          .then(res => setBookedTimes(res.data.booked_times || []));
      }
    } catch (err) {
      if (err.response?.status === 409) setMessage(err.response.data.error);
      else if (err.response?.status === 422) {
        const errs = err.response.data.errors;
        setMessage(errs ? Object.values(errs).flat().join(', ') : 'Please check all required fields.');
      } else {
        setMessage(err.response?.data?.error || err.response?.data?.message || 'Network error. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const isSuccess = message.includes('Thank you');
  const selectedService = services?.find(s => s.id.toString() === selectedServiceId);

  // Format selected date for display
  const displayDate = selectedDate
    ? new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    : '';

  return (
    <form onSubmit={handleSubmit} className="space-y-12 pb-32 md:pb-0">

      {/* Message Banner */}
      {message && (
        <div className={`border-l-4 px-6 py-5 ${isSuccess ? 'border-green-500 bg-green-50' : 'border-[var(--sienna)] bg-[var(--cream)]'}`}>
          <div className={`mb-1.5 font-[Unbounded] text-[10px] font-semibold tracking-[0.25em] uppercase ${isSuccess ? 'text-green-600' : 'text-[var(--sienna)]'}`}>
            {isSuccess ? '✓ Booking Confirmed' : 'Notice'}
          </div>
          <p className="text-[15px] text-[var(--ink)] leading-relaxed">{message}</p>
        </div>
      )}

      {/* Auth notice */}
      {isAuthenticated && (
        <div className="flex items-center gap-4 border-l-4 border-[var(--terra)] bg-[var(--cream)] px-6 py-4">
          <svg className="w-5 h-5 text-[var(--terra)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span className="text-[14px] text-[var(--ink)]">Your information has been pre-filled. You can modify it if needed.</span>
        </div>
      )}

      {/* ── Step 01: Your Details ── */}
      <section>
        <div className="mb-8 flex items-center gap-5 border-b-2 border-[var(--cream)] pb-5">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center bg-[var(--terra)] font-[Unbounded] text-sm font-bold text-white">
            01
          </div>
          <div>
            <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase mb-1">
              Step 01
            </div>
            <h3 className="font-[Libre_Baskerville] text-2xl italic text-[var(--ink)]">Your Details</h3>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
          {/* Name */}
          <div>
            <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
              Full Name <span className="opacity-60">*</span>
            </label>
            <input
              type="text"
              placeholder="Your full name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              className="w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition"
            />
          </div>

          {/* Email */}
          <div>
            <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
              Email <span className="opacity-60">*</span>
            </label>
            <input
              type="email"
              placeholder="your@email.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition"
            />
          </div>

          {/* Phone */}
          <div>
            <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
              WhatsApp Number <span className="opacity-60">*</span>
            </label>
            <div className="flex">
              <span className="inline-flex items-center border-2 border-r-0 border-[var(--cream)] bg-[var(--ink)] px-4 font-[Unbounded] text-[11px] font-semibold tracking-wider text-white/70">
                +94
              </span>
              <input
                type="tel"
                placeholder="7X XXX XXXX"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                required
                className="w-full border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[15px] text-[var(--ink)] placeholder:text-[var(--muted)] outline-none focus:border-[var(--terra)] transition"
              />
            </div>
          </div>
        </div>

        {/* Date picker — full width */}
        <div>
          <label className="mb-2.5 block font-[Unbounded] text-[12px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
            Appointment Date <span className="opacity-60">*</span>
          </label>

          {/* Selected date display */}
          {selectedDate && (
            <div className="mb-3 flex items-center gap-3 border-l-4 border-[var(--terra)] bg-[var(--cream)] px-5 py-3">
              <svg className="w-4 h-4 text-[var(--terra)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span className="font-[Unbounded] text-[11px] font-bold tracking-wide text-[var(--ink)]">
                {displayDate}
              </span>
            </div>
          )}

          {/* Mobile: horizontal day strip */}
          <div className="md:hidden">
            <MobileDayStrip
              value={selectedDate}
              onChange={(d) => {
                setSelectedDate(d);
                setSelectedTime('');
                setSelectedSlots([]);
              }}
              minDate={todayStr}
            />
          </div>

          {/* Desktop: full calendar */}
          <div className="hidden md:block border-2 border-[var(--cream)] bg-[var(--white)] p-5">
            <CalendarPicker
              value={selectedDate}
              onChange={(d) => {
                setSelectedDate(d);
                setSelectedTime('');
                setSelectedSlots([]);
              }}
              minDate={todayStr}
            />
          </div>
        </div>
      </section>

      {/* ── Step 02: Choose a Service ── */}
      <section>
        {/* Desktop header */}
        <div className="hidden md:flex mb-8 items-center gap-5 border-b-2 border-[var(--cream)] pb-5">
          <div className={`flex h-10 w-10 shrink-0 items-center justify-center font-[Unbounded] text-sm font-bold transition-colors ${selectedServiceId ? 'bg-[var(--terra)] text-white' : 'border-2 border-[var(--terra)] text-[var(--terra)]'}`}>
            {selectedServiceId ? '✓' : '02'}
          </div>
          <div className="flex-1">
            <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase mb-1">
              Step 02
            </div>
            <h3 className="font-[Libre_Baskerville] text-2xl italic text-[var(--ink)]">Choose a Service</h3>
          </div>
          {selectedServiceId && (
            <span className="font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
              Selected ✓
            </span>
          )}
        </div>

        {/* Mobile header */}
        <div className="md:hidden mb-3 flex items-center justify-between">
          <h3 className="text-base font-semibold text-[var(--ink)]">Select service</h3>
          {selectedService && (
            <span className="text-xs text-[var(--terra)] font-medium">Selected</span>
          )}
        </div>

        {/* Mobile: custom themed dropdown */}
        <div className="md:hidden">
          <button
            type="button"
            onClick={() => setServiceDropdownOpen((v) => !v)}
            className="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left bg-[var(--cream)]/50 border border-[var(--cream)] transition-all focus:border-[var(--terra)] focus:outline-none"
          >
            <div className="min-w-0 flex-1">
              {selectedService ? (
                <>
                  <div className="text-[15px] font-semibold text-[var(--ink)] truncate">
                    {selectedService.title}
                  </div>
                  <div className="text-[12px] text-[var(--muted)] mt-0.5">
                    {selectedService.duration} mins
                    {salon?.show_price && selectedService.price != null && (
                      <span className="ml-2 text-[var(--sienna)] font-bold">LKR {selectedService.price}</span>
                    )}
                  </div>
                </>
              ) : (
                <span className="text-[15px] text-[var(--muted)]">Select a service</span>
              )}
            </div>
            <svg
              className={`w-4 h-4 text-[var(--terra)] transition-transform ${serviceDropdownOpen ? 'rotate-180' : ''}`}
              fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          {serviceDropdownOpen && (
            <div className="mt-2 bg-white border border-[var(--cream)] max-h-72 overflow-y-auto divide-y divide-[var(--cream)]">
              {!services || services.length === 0 ? (
                <p className="text-sm text-[var(--muted)] text-center py-4">No services available.</p>
              ) : (
                services.map((service) => {
                  const isSelected = selectedServiceId === service.id.toString();
                  return (
                    <button
                      key={service.id}
                      type="button"
                      onClick={() => {
                        setSelectedServiceId(service.id.toString());
                        setSelectedTime('');
                        setSelectedSlots([]);
                        setServiceDropdownOpen(false);
                      }}
                      className={`w-full flex items-center justify-between gap-3 px-4 py-3 text-left transition-colors ${
                        isSelected ? 'bg-[var(--terra)]/10' : 'hover:bg-[var(--cream)]/40'
                      }`}
                    >
                      <div className="min-w-0 flex-1">
                        <div className={`text-[14px] font-semibold truncate ${isSelected ? 'text-[var(--terra)]' : 'text-[var(--ink)]'}`}>
                          {service.title}
                        </div>
                        {service.duration != null && (
                          <div className="text-[12px] text-[var(--muted)] mt-0.5">
                            {service.duration} mins
                          </div>
                        )}
                      </div>
                      {salon?.show_price && service.price != null && (
                        <span className="shrink-0 text-sm font-bold text-[var(--sienna)]">
                          LKR {service.price}
                        </span>
                      )}
                    </button>
                  );
                })
              )}
            </div>
          )}
        </div>

        {/* Desktop: native select dropdown */}
        <div className="hidden md:block relative">
          <select
            value={selectedServiceId}
            onChange={(e) => {
              setSelectedServiceId(e.target.value);
              setSelectedTime('');
              setSelectedSlots([]);
            }}
            className="w-full appearance-none border-2 border-[var(--cream)] bg-[var(--sand)] px-5 py-4 text-[16px] text-[var(--ink)] outline-none focus:border-[var(--terra)] transition cursor-pointer"
          >
            <option value="">— Select a service —</option>
            {services && services.map((service) => (
              <option key={service.id} value={service.id.toString()}>
                {service.title}
                {service.duration ? ` · ${service.duration} mins` : ''}
                {salon?.show_price && service.price ? ` · LKR ${service.price}` : ''}
              </option>
            ))}
          </select>
          <div className="pointer-events-none absolute inset-y-0 right-4 flex items-center text-[var(--terra)]">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>

        {/* Selected service detail (desktop only - mobile shows selection inline on the card) */}
        {selectedService && (
          <div className="hidden md:flex mt-3 items-center gap-4 border-l-4 border-[var(--terra)] bg-[var(--cream)] px-5 py-3.5">
            <svg className="w-5 h-5 text-[var(--terra)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span className="text-[14px] font-bold text-[var(--ink)]">{selectedService.title}</span>
            <span className="text-[13px] text-[var(--muted)]">{selectedService.duration} mins</span>
            {salon?.show_price && (
              <span className="ml-auto font-[Unbounded] text-sm font-black text-[var(--sienna)]">LKR {selectedService.price}</span>
            )}
          </div>
        )}
      </section>

      {/* ── Step 03: Pick a Time ── */}
      <section>
        <div className="mb-8 flex items-center gap-5 border-b-2 border-[var(--cream)] pb-5">
          <div className={`flex h-10 w-10 shrink-0 items-center justify-center font-[Unbounded] text-sm font-bold transition-colors ${selectedTime ? 'bg-[var(--terra)] text-white' : 'border-2 border-[var(--terra)] text-[var(--terra)]'}`}>
            {selectedTime ? '✓' : '03'}
          </div>
          <div className="flex-1">
            <div className="font-[Unbounded] text-[10px] font-semibold tracking-[0.3em] text-[var(--terra)] uppercase mb-1">
              Step 03
            </div>
            <h3 className="font-[Libre_Baskerville] text-2xl italic text-[var(--ink)]">Pick a Time</h3>
          </div>
          {selectedTime && (
            <span className="font-[Unbounded] text-[10px] font-semibold tracking-[0.2em] text-[var(--terra)] uppercase">
              {selectedTime} ✓
            </span>
          )}
        </div>

        {timeSlots.length === 0 ? (
          <div className="flex items-center gap-4 border-l-4 border-[var(--cream)] bg-[var(--sand)] px-6 py-5">
            <span className="text-[14px] text-[var(--muted)]">Loading available time slots…</span>
          </div>
        ) : (
          <>
            {/* Legend */}
            <div className="mb-5 flex flex-wrap gap-5 text-[13px] text-[var(--ink)]">
              <span className="flex items-center gap-2">
                <span className="inline-block w-5 h-5 bg-[var(--terra)]" />
                Selected
              </span>
              <span className="flex items-center gap-2">
                <span className="inline-block w-5 h-5 border-2 border-[var(--cream)] bg-[var(--white)]" />
                Available
              </span>
              <span className="flex items-center gap-2">
                <span className="inline-block w-5 h-5 border-2 border-[var(--cream)] bg-[var(--cream)] opacity-50" />
                Unavailable
              </span>
            </div>

            {/* Mobile: wrapping grid of tiles (desktop card style) */}
            <div className="md:hidden grid grid-cols-3 gap-2">
              {timeSlots.map((slot, i) => {
                const isBooked = isTimeBooked(slot.value);
                const isPast = isTimePast(slot.value);
                const isUnavailable = isBooked || isPast;
                const isSelected = selectedSlots.includes(slot.value);
                const avail = getAvailableSlots(slot.value);

                return (
                  <button
                    key={i}
                    type="button"
                    onClick={() => handleTimeClick(slot.value)}
                    disabled={isUnavailable}
                    className={`py-3 px-2 text-center border-2 transition-all ${
                      isSelected
                        ? 'border-[var(--terra)] bg-[var(--terra)] text-white shadow-[0_4px_12px_rgba(196,98,45,0.3)]'
                        : isUnavailable
                        ? 'border-[var(--cream)] bg-[var(--cream)] text-[var(--muted)] cursor-not-allowed opacity-40'
                        : 'border-[var(--cream)] bg-[var(--white)] text-[var(--ink)]'
                    }`}
                  >
                    <span className="block font-[Unbounded] text-[10px] font-semibold tracking-widest">
                      {slot.display}
                    </span>
                    {isPast && <span className="block text-[9px] mt-0.5 opacity-60">Past</span>}
                    {isBooked && !isPast && <span className="block text-[9px] mt-0.5">Full</span>}
                    {!isUnavailable && !isSelected && avail < staffCount && (
                      <span className="block text-[9px] mt-0.5 text-[var(--terra2)]">{avail} left</span>
                    )}
                  </button>
                );
              })}
            </div>

            {/* Desktop: original grid */}
            <div className="hidden md:grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
              {timeSlots.map((slot, i) => {
                const isBooked = isTimeBooked(slot.value);
                const isPast = isTimePast(slot.value);
                const isUnavailable = isBooked || isPast;
                const isSelected = selectedSlots.includes(slot.value);
                const avail = getAvailableSlots(slot.value);

                return (
                  <button
                    key={i}
                    type="button"
                    onClick={() => handleTimeClick(slot.value)}
                    disabled={isUnavailable}
                    className={`py-3 px-2 text-center border-2 transition-all ${
                      isSelected
                        ? 'border-[var(--terra)] bg-[var(--terra)] text-white shadow-[0_4px_12px_rgba(196,98,45,0.3)]'
                        : isUnavailable
                        ? 'border-[var(--cream)] bg-[var(--cream)] text-[var(--muted)] cursor-not-allowed opacity-40'
                        : 'border-[var(--cream)] bg-[var(--white)] text-[var(--ink)] hover:border-[var(--terra)] hover:bg-[var(--sand)]'
                    }`}
                  >
                    <span className="block font-[Unbounded] text-[10px] font-semibold tracking-widest">
                      {slot.display}
                    </span>
                    {isPast && <span className="block text-[9px] mt-0.5 opacity-60">Past</span>}
                    {isBooked && !isPast && <span className="block text-[9px] mt-0.5">Full</span>}
                    {!isUnavailable && !isSelected && avail < staffCount && (
                      <span className="block text-[9px] mt-0.5 text-[var(--terra2)]">{avail} left</span>
                    )}
                  </button>
                );
              })}
            </div>
          </>
        )}
      </section>

      {/* ── Submit (Desktop) ── */}
      <div className="hidden md:block border-t-2 border-[var(--cream)] pt-8">
        {selectedService && selectedTime && (
          <div className="mb-6 flex items-center gap-5 border-l-4 border-[var(--terra)] bg-[var(--cream)] px-6 py-5">
            <div className="font-[Unbounded] text-3xl font-black text-[var(--terra)] leading-none">
              {selectedService.duration}
              <span className="text-base font-normal text-[var(--muted)] ml-1">min</span>
            </div>
            <div className="flex-1">
              <div className="text-[15px] font-bold text-[var(--ink)]">{selectedService.title}</div>
              <div className="text-[13px] text-[var(--muted)] mt-0.5">at {selectedTime} · {displayDate}</div>
            </div>
            {salon?.show_price && (
              <div className="font-[Unbounded] text-base font-black text-[var(--sienna)]">
                LKR {selectedService.price}
              </div>
            )}
          </div>
        )}

        <button
          type="submit"
          disabled={loading || !selectedTime || selectedSlots.length === 0}
          className="w-full inline-flex items-center justify-center gap-3 bg-[var(--terra)] py-5 px-6 font-[Unbounded] text-[11px] font-bold tracking-[0.2em] text-white uppercase transition-all hover:bg-[var(--sienna)] hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
        >
          {loading ? (
            <>
              <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Processing…
            </>
          ) : (
            <>
              Book Appointment
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
              </svg>
            </>
          )}
        </button>
      </div>

      {/* ── Mobile sticky bottom bar ── */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-[var(--cream)] px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-6px_20px_rgba(0,0,0,0.06)]">
        {selectedService && selectedTime && (
          <div className="mb-3 flex items-center justify-between bg-[var(--cream)]/50 rounded-xl px-4 py-2.5">
            <div className="flex items-center gap-2 min-w-0">
              <svg className="w-4 h-4 text-[var(--terra)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span className="text-[13px] font-medium text-[var(--ink)] truncate">
                {new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
              </span>
            </div>
            <span className="text-[13px] font-semibold text-[var(--ink)] shrink-0 ml-3">
              {selectedTime}
            </span>
          </div>
        )}
        <button
          type="submit"
          disabled={loading || !selectedTime || selectedSlots.length === 0}
          className="w-full inline-flex items-center justify-center gap-2 bg-[var(--terra)] py-4 text-base font-semibold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {loading ? 'Processing…' : 'Book Now'}
        </button>
      </div>
    </form>
  );
}
