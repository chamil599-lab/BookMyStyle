<?php

namespace App\Services;

use App\Models\FavoriteSalon;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function salons(User $user): Builder
    {
        return app(PublicCatalogue::class)->cardSalons()->whereIn('salons.id',
            FavoriteSalon::where('user_id', $user->id)->select('salon_id'));
    }

    public function add(User $user, int $salonId): bool
    {
        return $this->mutate($user, $salonId, 'add');
    }

    public function toggle(User $user, int $salonId): bool
    {
        return $this->mutate($user, $salonId, 'toggle');
    }

    public function remove(User $user, int $salonId): bool
    {
        return $this->mutate($user, $salonId, 'remove');
    }

    private function mutate(User $user, int $salonId, string $action): bool
    {
        return DB::transaction(function () use ($user, $salonId, $action) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($action !== 'remove') {
                Salon::publiclyVisible()->findOrFail($salonId);
            }
            $query = FavoriteSalon::where('user_id', $user->id)->where('salon_id', $salonId);
            $exists = $query->exists();
            if ($action === 'remove' || ($action === 'toggle' && $exists)) {
                $query->delete();

                return $action === 'remove' ? $exists : false;
            }
            if ($exists) {
                return false;
            }
            FavoriteSalon::create(['user_id' => $user->id, 'salon_id' => $salonId]);

            return true;
        }, 3);
    }
}
