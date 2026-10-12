import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import api from '../../utils/api';

export default function ChangePassword() {
  const [formData, setFormData] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const changePasswordMutation = useMutation({
    mutationFn: async (data) => {
      const response = await api.post('/auth/change-password', data);
      return response.data;
    },
    onSuccess: () => {
      setSuccess('Password changed successfully!');
      setFormData({ current_password: '', password: '', password_confirmation: '' });
      setTimeout(() => setSuccess(''), 3000);
    },
    onError: (mutationError) => {
      setError(mutationError.response?.data?.message || 'Failed to change password');
      setTimeout(() => setError(''), 3000);
    },
  });

  const handleChange = (e) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    setError('');
    setSuccess('');
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    if (formData.password !== formData.password_confirmation) {
      setError('Passwords do not match');
      return;
    }

    if (formData.password.length < 8) {
      setError('Password must be at least 8 characters');
      return;
    }

    changePasswordMutation.mutate(formData);
  };

  return (
    <div className="space-y-6">
      <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
        <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-ds-outline">Security</p>
        <h1 className="mt-2 font-headline text-3xl font-extrabold text-[#191c1d]">Change Password</h1>
        <p className="mt-2 text-sm text-ds-outline">Keep your account secure with a strong password that only you know.</p>
      </div>

      <div className="grid gap-6 xl:grid-cols-[0.72fr_1.28fr]">
        <div className="rounded-[1.2rem] bg-[linear-gradient(135deg,#2d5252_0%,#476363_58%,#5f7e7d_100%)] p-6 text-white shadow-[0_18px_42px_rgba(45,82,82,0.18)]">
          <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/12">
            <span className="material-symbols-outlined text-3xl text-white">lock</span>
          </div>
          <h2 className="mt-5 font-headline text-2xl font-extrabold text-white">Security Tips</h2>
          <div className="mt-4 space-y-3 text-sm text-white/72">
            <p>Use at least 8 characters with a mix of letters, numbers, and symbols.</p>
            <p>Avoid reusing passwords from other apps or websites.</p>
            <p>Update it right away if you think your account may have been exposed.</p>
          </div>
        </div>

        <div className="max-w-2xl rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          {error && <div className="mb-4 rounded-[0.9rem] border border-[#f0b2ac] bg-[#fff1ef] p-3 text-sm text-ds-error">{error}</div>}
          {success && <div className="mb-4 rounded-[0.9rem] border border-[#b8d9c0] bg-[#edf7ef] p-3 text-sm text-[#2e7d32]">{success}</div>}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label htmlFor="current_password" className="mb-2 block text-sm font-semibold text-ds-on-surface-variant">
                Current Password
              </label>
              <input
                id="current_password"
                name="current_password"
                type="password"
                required
                value={formData.current_password}
                onChange={handleChange}
                className="w-full rounded-2xl border border-[#d3dfdf] bg-white px-4 py-3 text-[#191c1d] outline-hidden transition focus:border-ds-primary focus:ring-4 focus:ring-ds-primary/10"
              />
            </div>

            <div>
              <label htmlFor="password" className="mb-2 block text-sm font-semibold text-ds-on-surface-variant">
                New Password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                required
                value={formData.password}
                onChange={handleChange}
                className="w-full rounded-2xl border border-[#d3dfdf] bg-white px-4 py-3 text-[#191c1d] outline-hidden transition focus:border-ds-primary focus:ring-4 focus:ring-ds-primary/10"
              />
              <p className="mt-2 text-xs font-semibold text-ds-outline">Must be at least 8 characters</p>
            </div>

            <div>
              <label htmlFor="password_confirmation" className="mb-2 block text-sm font-semibold text-ds-on-surface-variant">
                Confirm New Password
              </label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                value={formData.password_confirmation}
                onChange={handleChange}
                className="w-full rounded-2xl border border-[#d3dfdf] bg-white px-4 py-3 text-[#191c1d] outline-hidden transition focus:border-ds-primary focus:ring-4 focus:ring-ds-primary/10"
              />
            </div>

            <button
              type="submit"
              disabled={changePasswordMutation.isPending}
              className="w-full rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-4 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition disabled:cursor-not-allowed disabled:opacity-50"
            >
              {changePasswordMutation.isPending ? 'Changing Password...' : 'Change Password'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
