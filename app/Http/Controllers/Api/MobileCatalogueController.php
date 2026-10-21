<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileCatalogueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'department_id' => 'nullable|integer|min:1',
            'sort' => ['nullable', Rule::in(['rating', 'name'])], 'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);
        $query = Salon::publiclyVisible()->select(['salons.id', 'salons.name', 'salons.location', 'salons.created_by'])
            ->with(['media' => fn ($q) => $q->where('collection_name', 'cover')])
            ->withAvg('ratings as public_rating', 'rating')->withCount('ratings')
            ->withCount(['services as service_count' => fn ($q) => $q->published()]);
        if ($term = trim($filters['q'] ?? '')) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $query->where(function ($q) use ($term) {
                foreach (['name', 'location', 'description'] as $column) {
                    $q->orWhereRaw("LOWER(salons.{$column}) LIKE LOWER(?) ESCAPE '!'", [$term]);
                }
                $q->orWhereHas('services', fn ($services) => $services->published()
                    ->whereRaw("LOWER(title) LIKE LOWER(?) ESCAPE '!'", [$term]));
            });
        }
        if ($department = $filters['department_id'] ?? null) {
            $query->whereHas('services', fn ($q) => $q->published()->whereHas('department',
                fn ($q) => $q->whereKey($department)->where('is_active', true)));
        }
        if (($filters['sort'] ?? 'rating') === 'name') {
            $query->orderBy('name');
        } else {
            $query->orderByDesc('public_rating')->orderByDesc('ratings_count');
        }
        $page = $query->orderBy('salons.id')->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($salon) => [
                'id' => $salon->id, 'name' => $salon->name, 'location' => $salon->location,
                'cover_url' => $salon->getFirstMediaUrl('cover', 'thumb') ?: $salon->getFirstMediaUrl('cover') ?: null,
                'rating' => (float) ($salon->public_rating ?? 0), 'review_count' => $salon->ratings_count,
                'service_count' => $salon->service_count,
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }
}
