import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { format } from 'date-fns';
import api from '../../utils/api';
import { useAuth } from '../../context/AuthContext';

export default function ProfilePage() {
  const { updateUser } = useAuth();
  const queryClient = useQueryClient();
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({ name: '', email: '' });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const { data: profile, isLoading } = useQuery({
    queryKey: ['profile'],
    queryFn: async () => {
      const response = await api.get('/customer/profile');
      const profileData = response.data.data;
      setFormData({ name: profileData.name, email: profileData.email });
      return profileData;
    },
  });

  const updateMutation = useMutation({
    mutationFn: async (data) => {
      const response = await api.put('/customer/profile', data);
      return response.data;
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
      updateUser(data.data);
      setIsEditing(false);
      setSuccess('Profile updated successfully!');
      setTimeout(() => setSuccess(''), 3000);
    },
    onError: (mutationError) => {
      setError(mutationError.response?.data?.message || 'Failed to update profile');
      setTimeout(() => setError(''), 3000);
    },
  });

  const handleChange = (e) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    updateMutation.mutate(formData);
  };

  const handleCancel = () => {
    setIsEditing(false);
    setFormData({ name: profile.name, email: profile.email });
    setError('');
  };

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <div className="h-12 w-12 animate-spin rounded-full border-b-2 border-[#004242]" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
        <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#6f7979]">Account Settings</p>
        <h1 className="mt-2 font-headline text-3xl font-extrabold text-[#191c1d]">Profile</h1>
        <p className="mt-2 text-sm text-[#6f7979]">Keep your personal information current so booking updates always reach you.</p>
      </div>

      <div className="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        <div className="rounded-[1.2rem] bg-[linear-gradient(135deg,#004242_0%,#005b5c_58%,#1b6869_100%)] p-6 text-white shadow-[0_18px_42px_rgba(0,66,66,0.18)]">
          <div className="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-white/12 text-2xl font-bold text-white">
            {profile?.name?.charAt(0)?.toUpperCase() ?? 'C'}
          </div>
          <p className="mt-5 text-[10px] font-bold uppercase tracking-[0.24em] text-[#8bd0d1]">Member Since</p>
          <h2 className="mt-2 font-headline text-2xl font-extrabold text-white">
            {profile?.created_at ? format(new Date(profile.created_at), 'MMMM yyyy') : 'Recently joined'}
          </h2>
          <p className="mt-3 text-sm leading-relaxed text-white/72">
            Your profile helps salons confirm appointments and contact details quickly when needed.
          </p>
          <div className="mt-6 rounded-[1rem] border border-white/12 bg-white/10 p-4">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-[#8bd0d1]">Email</p>
            <p className="mt-2 text-sm font-semibold text-white">{profile?.email}</p>
          </div>
        </div>

        <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          {error && <div className="mb-4 rounded-[0.9rem] border border-[#f0b2ac] bg-[#fff1ef] p-3 text-sm text-[#ba1a1a]">{error}</div>}
          {success && <div className="mb-4 rounded-[0.9rem] border border-[#b8d9c0] bg-[#edf7ef] p-3 text-sm text-[#2e7d32]">{success}</div>}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label htmlFor="name" className="mb-2 block text-sm font-semibold text-[#3f4948]">
                Full Name
              </label>
              <input
                id="name"
                name="name"
                type="text"
                required
                value={formData.name}
                onChange={handleChange}
                disabled={!isEditing}
                className="w-full rounded-[1rem] border border-[#d3dfdf] bg-white px-4 py-3 text-[#191c1d] outline-none transition focus:border-[#004242] focus:ring-4 focus:ring-[#004242]/10 disabled:cursor-not-allowed disabled:bg-[#f7f9f9]"
              />
            </div>

            <div>
              <label htmlFor="email" className="mb-2 block text-sm font-semibold text-[#3f4948]">
                Email Address
              </label>
              <input
                id="email"
                name="email"
                type="email"
                required
                value={formData.email}
                onChange={handleChange}
                disabled={!isEditing}
                className="w-full rounded-[1rem] border border-[#d3dfdf] bg-white px-4 py-3 text-[#191c1d] outline-none transition focus:border-[#004242] focus:ring-4 focus:ring-[#004242]/10 disabled:cursor-not-allowed disabled:bg-[#f7f9f9]"
              />
            </div>

            <div className="rounded-[1rem] bg-[#f5f8f8] px-4 py-4">
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-[#6f7979]">Member Since</p>
              <p className="mt-2 text-sm font-semibold text-[#191c1d]">
                {profile?.created_at ? format(new Date(profile.created_at), 'MMMM d, yyyy') : 'Not available'}
              </p>
            </div>

            <div className="flex flex-wrap gap-3">
              {!isEditing ? (
                <button
                  type="button"
                  onClick={() => setIsEditing(true)}
                  className="rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-6 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition hover:translate-y-[-1px]"
                >
                  Edit Profile
                </button>
              ) : (
                <>
                  <button
                    type="submit"
                    disabled={updateMutation.isPending}
                    className="rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-6 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
                  </button>
                  <button
                    type="button"
                    onClick={handleCancel}
                    className="rounded-full border border-[#d0dddd] px-6 py-3 text-sm font-bold text-[#3f4948] transition hover:bg-[#f5f8f8]"
                  >
                    Cancel
                  </button>
                </>
              )}
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
