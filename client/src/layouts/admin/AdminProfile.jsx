import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import api from '../../utils/api';
import { useAuth } from '../../context/AuthContext';

export default function AdminProfile() {
  const { user, updateUser } = useAuth();

  const initials = user?.name
    ? user.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
    : 'A';

  /* ── Profile form ── */
  const [form, setForm]      = useState({ name: user?.name ?? '', email: user?.email ?? '' });
  const [profileMsg, setMsg] = useState(null);

  const profileMut = useMutation({
    mutationFn: data => api.put('/customer/profile', data).then(r => r.data),
    onSuccess: data => {
      updateUser({ name: data.data?.name ?? form.name, email: data.data?.email ?? form.email });
      setMsg({ type: 'success', text: 'Profile updated successfully.' });
      setTimeout(() => setMsg(null), 4000);
    },
    onError: e => {
      const msg = e.response?.data?.message
        ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
        ?? 'Failed to update profile.';
      setMsg({ type: 'error', text: msg });
    },
  });

  /* ── Password form ── */
  const [pw, setPw]       = useState({ current_password: '', password: '', password_confirmation: '' });
  const [pwMsg, setPwMsg] = useState(null);

  const pwMut = useMutation({
    mutationFn: data => api.post('/auth/change-password', data).then(r => r.data),
    onSuccess: () => {
      setPw({ current_password: '', password: '', password_confirmation: '' });
      setPwMsg({ type: 'success', text: 'Password changed successfully.' });
      setTimeout(() => setPwMsg(null), 4000);
    },
    onError: e => {
      const msg = e.response?.data?.message
        ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
        ?? 'Failed to change password.';
      setPwMsg({ type: 'error', text: msg });
    },
  });

  const inp = 'w-full bg-ds-surface-container-low border border-transparent rounded-xl px-4 py-2.5 text-sm text-[#191c1d] placeholder-ds-outline focus:outline-hidden focus:border-ds-primary/40 focus:bg-white transition-colors';
  const lbl = 'block text-xs font-bold text-ds-outline uppercase tracking-widest mb-1.5';

  const memberSince = user?.created_at
    ? new Date(user.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    : null;

  return (
    <div className="space-y-6 max-w-2xl">

      {/* ── Header card ── */}
      <div className="bg-white rounded-lg p-6 flex items-center gap-5">
        <div className="w-16 h-16 rounded-full bg-ds-primary-container flex items-center justify-center text-white font-black text-xl shrink-0">
          {initials}
        </div>
        <div className="min-w-0">
          <h1 className="font-headline text-xl font-bold text-[#191c1d] truncate">{user?.name}</h1>
          <p className="text-sm text-ds-outline">{user?.email}</p>
          <div className="flex items-center gap-2 mt-1.5">
            <span className="text-xs bg-ds-primary-container/10 text-ds-primary px-2 py-0.5 rounded-full font-semibold">System Admin</span>
            {memberSince && (
              <span className="text-[11px] text-ds-outline">Member since {memberSince}</span>
            )}
          </div>
        </div>
      </div>

      {/* ── Edit profile ── */}
      <div className="bg-white rounded-lg overflow-hidden">
        <div className="px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-base font-bold text-[#191c1d]">Account Details</h2>
          <p className="text-sm text-ds-outline">Update your name and email address</p>
        </div>
        <form
          onSubmit={e => { e.preventDefault(); profileMut.mutate(form); }}
          className="px-6 py-5 space-y-4"
        >
          {profileMsg && (
            <div className={`px-4 py-3 rounded-xl text-sm font-medium border-l-4 ${
              profileMsg.type === 'success'
                ? 'bg-[#e8f5e9] text-[#2e7d32] border-[#2e7d32]'
                : 'bg-ds-error-container text-ds-error border-ds-error'
            }`}>{profileMsg.text}</div>
          )}

          <div>
            <label className={lbl}>Full Name</label>
            <input
              value={form.name}
              onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
              required
              className={inp}
              placeholder="Your full name"
            />
          </div>
          <div>
            <label className={lbl}>Email Address</label>
            <input
              type="email"
              value={form.email}
              onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
              required
              className={inp}
              placeholder="you@example.com"
            />
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={profileMut.isPending}
              className="flex items-center gap-2 px-6 py-2.5 bg-ds-primary text-white text-sm font-bold rounded-xl hover:bg-ds-primary-container transition-colors disabled:opacity-60"
            >
              {profileMut.isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              Save Changes
            </button>
          </div>
        </form>
      </div>

      {/* ── Change password ── */}
      <div className="bg-white rounded-lg overflow-hidden">
        <div className="px-6 py-5 border-b border-[#e1e3e4]">
          <h2 className="font-headline text-base font-bold text-[#191c1d]">Change Password</h2>
          <p className="text-sm text-ds-outline">Use a strong password of at least 8 characters</p>
        </div>
        <form
          onSubmit={e => { e.preventDefault(); pwMut.mutate(pw); }}
          className="px-6 py-5 space-y-4"
        >
          {pwMsg && (
            <div className={`px-4 py-3 rounded-xl text-sm font-medium border-l-4 ${
              pwMsg.type === 'success'
                ? 'bg-[#e8f5e9] text-[#2e7d32] border-[#2e7d32]'
                : 'bg-ds-error-container text-ds-error border-ds-error'
            }`}>{pwMsg.text}</div>
          )}

          <div>
            <label className={lbl}>Current Password</label>
            <input
              type="password"
              value={pw.current_password}
              onChange={e => setPw(p => ({ ...p, current_password: e.target.value }))}
              required
              className={inp}
              placeholder="••••••••"
            />
          </div>
          <div>
            <label className={lbl}>New Password</label>
            <input
              type="password"
              value={pw.password}
              onChange={e => setPw(p => ({ ...p, password: e.target.value }))}
              required
              minLength={8}
              className={inp}
              placeholder="••••••••"
            />
          </div>
          <div>
            <label className={lbl}>Confirm New Password</label>
            <input
              type="password"
              value={pw.password_confirmation}
              onChange={e => setPw(p => ({ ...p, password_confirmation: e.target.value }))}
              required
              className={inp}
              placeholder="••••••••"
            />
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={pwMut.isPending}
              className="flex items-center gap-2 px-6 py-2.5 bg-[#191c1d] text-white text-sm font-bold rounded-xl hover:bg-ds-on-surface-variant transition-colors disabled:opacity-60"
            >
              {pwMut.isPending && <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />}
              Update Password
            </button>
          </div>
        </form>
      </div>

    </div>
  );
}
