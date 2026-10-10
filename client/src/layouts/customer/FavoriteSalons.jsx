import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../utils/api';

export default function FavoriteSalons() {
  const queryClient = useQueryClient();
  const [removingId, setRemovingId] = useState(null);

  const { data: favorites, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => {
      const response = await api.get('/customer/favorites');
      return response.data.data;
    },
  });

  const removeFavoriteMutation = useMutation({
    mutationFn: async (salonId) => {
      const response = await api.post(`/customer/favorites/${salonId}/toggle`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['favorites'] });
      setRemovingId(null);
    },
  });

  const handleRemoveFavorite = (salonId) => {
    setRemovingId(salonId);
    removeFavoriteMutation.mutate(salonId);
  };

  if (isLoading) {
    return (
      <div className="flex min-h-[400px] items-center justify-center">
        <div className="h-12 w-12 animate-spin rounded-full border-b-2 border-[#004242]" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-white p-6 shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
        <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-[#6f7979]">Saved List</p>
        <h1 className="mt-2 font-headline text-3xl font-extrabold text-[#191c1d]">Favorite Salons</h1>
        <p className="mt-2 text-sm text-[#6f7979]">Your saved salon collection for faster rebooking and easier comparison.</p>
      </div>

      {!favorites || favorites.length === 0 ? (
        <div className="rounded-[1.2rem] border border-[#d7e4e3] bg-[linear-gradient(180deg,#ffffff_0%,#f8fbfb_100%)] p-12 text-center shadow-[0_18px_42px_rgba(25,28,29,0.08)]">
          <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-[linear-gradient(135deg,#7a3e12_0%,#e0912d_100%)] text-white shadow-[0_18px_34px_rgba(122,62,18,0.18)]">
            <svg className="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
          <h3 className="mt-5 font-headline text-2xl font-bold text-[#191c1d]">No Favorite Salons Yet</h3>
          <p className="mt-2 text-sm text-[#6f7979]">Start exploring and save your favorite salons.</p>
          <Link to="/salons">
            <button className="mt-6 rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-6 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.18)] transition hover:translate-y-[-1px]">
              Browse Salons
            </button>
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {favorites.map((favorite) => (
            <div key={favorite.id} className="group overflow-hidden rounded-[1.2rem] border border-[#d7e4e3] bg-white shadow-[0_18px_42px_rgba(25,28,29,0.08)] transition hover:translate-y-[-2px]">
              <div className="relative h-48 overflow-hidden bg-gray-200">
                {favorite.image ? (
                  <img src={favorite.image} alt={favorite.name} className="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                ) : (
                  <div className="flex h-full w-full items-center justify-center bg-[linear-gradient(135deg,#eef6f6_0%,#dfeeed_100%)]">
                    <svg className="h-16 w-16 text-[#8aa6a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                )}

                <button
                  onClick={() => handleRemoveFavorite(favorite.id)}
                  disabled={removingId === favorite.id}
                  className="absolute right-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-lg transition hover:scale-105 hover:bg-white disabled:opacity-50"
                >
                  {removingId === favorite.id ? (
                    <div className="h-5 w-5 animate-spin rounded-full border-b-2 border-[#ba1a1a]" />
                  ) : (
                    <svg className="h-5 w-5 text-[#ba1a1a]" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clipRule="evenodd" />
                    </svg>
                  )}
                </button>
              </div>

              <div className="p-5">
                <h3 className="mb-2 font-headline text-lg font-bold text-[#191c1d]">{favorite.name}</h3>

                {favorite.address && (
                  <p className="mb-2 flex items-start text-sm text-[#6f7979]">
                    <svg className="mr-2 mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span className="line-clamp-2">{favorite.address}</span>
                  </p>
                )}

                {favorite.phone && (
                  <p className="mb-4 flex items-center text-sm text-[#6f7979]">
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    {favorite.phone}
                  </p>
                )}

                <Link to={`/salon/${favorite.id}`}>
                  <button className="w-full rounded-full bg-[linear-gradient(135deg,#004242_0%,#0d6667_100%)] px-4 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(0,66,66,0.16)] transition hover:translate-y-[-1px]">
                    View Details
                  </button>
                </Link>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
