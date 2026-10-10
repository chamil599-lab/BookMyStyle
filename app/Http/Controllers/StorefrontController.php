<?php

namespace App\Http\Controllers;

use App\Models\DealsPage;
use App\Models\ImageCycle;
use App\Models\SalonRating;
use App\Models\ServicePage;
use App\Models\TeamMember;
use App\Services\PublicCatalogue;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function directory(Request $request, PublicCatalogue $catalogue)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'department_id' => 'nullable|integer|exists:departments,id',
            'sort' => 'nullable|in:rating,name,reviews,newest', 'page' => 'nullable|integer|min:1|max:10000']);
        $mode = $request->route()->getName();
        $title = match ($mode) {
            'home' => 'Book your absolute best.', 'trending' => 'Trending salons', 'popular' => 'Popular salons', default => 'Explore our salons'
        };

        return view('storefront.directory', ['salons' => $catalogue->directory($filters, $mode),
            'departments' => $catalogue->departments(), 'filters' => $filters, 'mode' => $mode, 'title' => $title,
            'stats' => $catalogue->stats(), 'images' => $mode === 'home' ? ImageCycle::where('status', 1)->orderBy('order')->get() : collect()]);
    }

    public function salon(int $id, PublicCatalogue $catalogue)
    {
        $salon = $catalogue->find($id);

        return view('storefront.salon', ['salon' => $salon, 'title' => $salon->name,
            'reviews' => SalonRating::where('salon_id', $salon->id)->latest()->paginate(10, ['id', 'rating', 'review', 'created_at'])]);
    }

    public function content(Request $request, PublicCatalogue $catalogue)
    {
        $mode = $request->route()->getName();
        $filters = $request->validate(['category' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:10000']);
        $query = $mode === 'deals' ? DealsPage::where('status', 1) : ServicePage::where('status', 'published');
        $column = $mode === 'deals' ? 'category' : 'category_name';
        $categories = (clone $query)->whereNotNull($column)->distinct()->orderBy($column)->pluck($column);
        if ($filters['category'] ?? null) {
            $query->where($column, $filters['category']);
        }

        return view('storefront.content', ['items' => $query->latest()->paginate(12)->withQueryString(), 'categories' => $categories,
            'mode' => $mode, 'title' => $mode === 'deals' ? 'Beauty, with a little extra.' : 'Find your signature style.']);
    }

    public function about(PublicCatalogue $catalogue)
    {
        return view('storefront.about', ['title' => 'About BookMyStyle', 'stats' => $catalogue->stats(),
            'team' => TeamMember::where('status', true)->orderBy('order')->get()]);
    }

    public function sitemap()
    {
        return response()->view('storefront.sitemap', ['salons' => \App\Models\Salon::publiclyVisible()->orderBy('id')->get(['id', 'updated_at'])])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /filament\nDisallow: /dashboard\nDisallow: /owner\nDisallow: /admin\nDisallow: /profile\nDisallow: /settings/\nDisallow: /login\nDisallow: /register\nDisallow: /forgot-password\nDisallow: /reset-password\nDisallow: /subscription/\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
