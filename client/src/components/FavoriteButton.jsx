import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import api from '../utils/api';

export default function FavoriteButton({ salonId }) {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();
  const queryClient = useQueryClient();
  const [showLoginPrompt, setShowLoginPrompt] = useState(false);

  // Check if salon is favorited
  const { data: favoriteStatus } = useQuery({
    queryKey: ['favorite-status', salonId],
    queryFn: async () => {
      if (!isAuthenticated) return { favorited: false };
      const response = await api.get(`/customer/favorites/${salonId}/check`);
      return response.data;
    },
    enabled: isAuthenticated,
  });

  // Toggle favorite mutation
  const toggleFavoriteMutation = useMutation({
    mutationFn: async () => {
      const response = await api.post(`/customer/favorites/${salonId}/toggle`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['favorite-status', salonId]);
      queryClient.invalidateQueries(['favorites']);
    },
  });

  const handleClick = (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (!isAuthenticated) {
      setShowLoginPrompt(true);
      return;
    }
    toggleFavoriteMutation.mutate();
  };

  const isFavorited = favoriteStatus?.favorited || false;

  return (
    <>
      {/* Favorite Button */}
      <button
        onClick={handleClick}
  disabled={toggleFavoriteMutation.isLoading}
        className={`w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 shadow-lg hover:scale-110 disabled:opacity-50 ${
          isFavorited 
            ? 'bg-(--terra) hover:bg-(--sienna)'
            : 'bg-(--white) hover:bg-(--sand) border-2 border-(--cream)'
        }`}
        title={isFavorited ? 'Remove from favorites' : 'Add to favorites'}
      >
        {toggleFavoriteMutation.isLoading ? (
          <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-(--terra)"></div>
        ) : (
          <svg
            className={`w-6 h-6 transition-all ${
              isFavorited
                ? 'text-white fill-current'
                : 'text-(--muted) hover:text-(--terra)'
            }`}
            fill={isFavorited ? 'currentColor' : 'none'}
            stroke="currentColor"
            strokeWidth={2}
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
        )}
      </button>

      {/* Login/Register Modal */}
      {showLoginPrompt && (
        <div 
          className="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4"
          onClick={() => setShowLoginPrompt(false)}
        >
          <div 
            className="bg-(--white) border-2 border-(--cream) p-8 max-w-md w-full transform transition-all"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Icon */}
            <div className="w-16 h-16 bg-(--cream) rounded-full flex items-center justify-center mx-auto mb-4">
              <svg className="w-8 h-8 text-(--terra)" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clipRule="evenodd" />
              </svg>
            </div>

            {/* Title */}
            <h3 className="text-2xl font-bold text-center text-(--ink) mb-2">
              Save to Favorites
            </h3>
            <p className="text-center text-(--muted) mb-8">
              Sign in to save this salon and access it anytime from your dashboard
            </p>

            {/* Buttons */}
            <div className="space-y-3">
              <button
                onClick={() => {
                  setShowLoginPrompt(false);
                  navigate('/login');
                }}
                className="w-full px-6 py-3 bg-(--terra) text-white rounded-lg font-medium hover:bg-(--sienna) transition-all shadow-md hover:shadow-lg"
              >
                Sign In
              </button>
              
              <button
                onClick={() => {
                  setShowLoginPrompt(false);
                  navigate('/register');
                }}
                className="w-full px-6 py-3 bg-(--white) text-(--ink) border-2 border-(--cream) rounded-lg font-medium hover:bg-(--sand) transition-all"
              >
                Create Account
              </button>

              <button
                onClick={() => setShowLoginPrompt(false)}
                className="w-full px-6 py-3 text-(--muted) hover:text-(--ink) font-medium transition-all"
              >
                Maybe Later
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
