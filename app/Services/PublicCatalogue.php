<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Department;
use App\Models\Salon;
use App\Models\SalonRating;
use Illuminate\Database\Eloquent\Builder;

class PublicCatalogue
{
    public function salons(): Builder
    {
        return Salon::publiclyVisible()->with('media')->withAvg('ratings as public_rating', 'rating')->withCount('ratings');
    }

    public function find(int $id): Salon
    {
        return $this->salons()->with(['services' => fn ($query) => $query->published()->with(['department', 'category']), 'owner'])->findOrFail($id);
    }

    public function cardSalons(): Builder
    {
        return $this->salons()
            ->withCount(['services as published_services_count' => fn ($services) => $services->published()])
            ->withMin(['services as starting_price' => fn ($services) => $services->published()], 'price')
            ->when(auth()->check(), fn ($query) => $query->addSelect([
                'is_favorited' => \App\Models\FavoriteSalon::query()->selectRaw('1')
                    ->whereColumn('salon_id', 'salons.id')->where('user_id', auth()->id())->limit(1),
            ]));
    }

    public function directory(array $filters, string $mode = 'salons')
    {
        $query = $this->cardSalons();
        if ($search = trim($filters['q'] ?? '')) {
            // Bound parameter, with literal wildcard characters escaped.
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function ($query) use ($search) {
                foreach (['name', 'location', 'description'] as $column) {
                    $query->orWhereRaw("LOWER(salons.{$column}) LIKE LOWER(?) ESCAPE '!'", [$search]);
                }
            });
        }
        if ($department = $filters['department_id'] ?? null) {
            $query->whereHas('services', fn ($query) => $query->published()->whereHas('department', fn ($query) => $query->whereKey($department)->where('is_active', true)));
        }
        if ($mode === 'trending' || $mode === 'popular') {
            $query->withCount(['bookings as booking_count' => function ($query) use ($mode) {
                $query->whereIn('status', [Booking::STATUS_APPROVED, 'completed']);
                if ($mode === 'trending') {
                    $query->where('created_at', '>=', now()->subDays(30))->where('created_at', '<=', now());
                }
            }])->orderByDesc('booking_count')->orderByDesc('public_rating');
        } else {
            match ($filters['sort'] ?? 'rating') {
                'name' => $query->orderBy('name'),
                'reviews' => $query->orderByDesc('ratings_count')->orderByDesc('public_rating'),
                'newest' => $query->orderByDesc('created_at'),
                default => $query->orderByDesc('public_rating')->orderByDesc('ratings_count'),
            };
        }

        return $query->orderBy('id')->paginate(12)->withQueryString();
    }

    public function departments()
    {
        return Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function stats(): array
    {
        $ids = Salon::publiclyVisible()->select('id');

        return [
            'salons' => Salon::publiclyVisible()->count(),
            'bookings' => Booking::whereIn('salon_id', clone $ids)->whereIn('status', ['approved', 'completed'])->count(),
            'clients' => Booking::whereIn('salon_id', clone $ids)->distinct()->count('email'),
            'rating' => round((float) SalonRating::whereIn('salon_id', clone $ids)->avg('rating'), 1),
        ];
    }
}
