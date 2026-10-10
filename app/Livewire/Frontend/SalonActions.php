<?php

namespace App\Livewire\Frontend;

use App\Models\FavoriteSalon;
use App\Models\User;
use App\Services\AuthActionLimiter;
use App\Services\PublicCatalogue;
use App\Services\SalonReviewService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SalonActions extends Component
{
    #[Locked]
    public int $salonId;

    public string $rating = '';

    public string $review = '';

    public function mount(int $salonId): void
    {
        $this->salonId = app(PublicCatalogue::class)->find($salonId)->id;
    }

    public function toggleFavorite(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasVerifiedEmail(), 403);
        app(AuthActionLimiter::class)->check('favorite', $user->email, 20, 'rating');
        app(PublicCatalogue::class)->find($this->salonId);
        DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $favorite = FavoriteSalon::where('user_id', $user->id)->where('salon_id', $this->salonId)->first();
            if ($favorite) {
                $favorite->delete();
            } else {
                FavoriteSalon::create(['user_id' => $user->id, 'salon_id' => $this->salonId]);
            }
        }, 3);
    }

    public function submitReview(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasVerifiedEmail(), 403);
        app(AuthActionLimiter::class)->check('review', $user->email, 5, 'rating');
        app(SalonReviewService::class)->submit(app(PublicCatalogue::class)->find($this->salonId), $user, $this->only('rating', 'review'));
        $this->redirect(route('salon.show', ['id' => $this->salonId]).'#reviews');
    }

    public function render()
    {
        $salon = app(PublicCatalogue::class)->find($this->salonId);
        $user = auth()->user();

        return view('livewire.frontend.salon-actions', ['eligibility' => $user ? app(SalonReviewService::class)->eligibility($salon, $user) : null,
            'favorited' => $user && FavoriteSalon::where('user_id', $user->id)->where('salon_id', $this->salonId)->exists()]);
    }
}
