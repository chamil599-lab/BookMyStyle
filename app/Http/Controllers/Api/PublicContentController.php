<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreImageCycleRequest;
use App\Models\Booking;
use App\Models\Dealspage;
use App\Models\Department;
use App\Models\ImageCycle;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Models\ServicePage;
use App\Models\SiteVisit;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicContentController extends Controller
{
    public function test(): JsonResponse
    {
        return response()->json(['message' => 'API is working!']);
    }

    public function departments(): JsonResponse
    {
        return response()->json(Department::where('is_active', 1)->get());
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'totalSalons' => Salon::count(),
            'totalClients' => Booking::distinct('email')->count('email'),
            'totalBookings' => Booking::count(),
            'approvedBookings' => Booking::where('status', Booking::STATUS_APPROVED)->count(),
            'averageRating' => round((float) (SalonRating::avg('rating') ?? 0), 1),
        ]);
    }

    public function recordVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'visitorId' => ['nullable', 'string', 'max:120'],
            'sessionId' => ['nullable', 'string', 'max:120'],
        ]);

        $path = '/'.ltrim($validated['path'], '/');
        $path = Str::of($path)->before('?')->limit(255, '')->toString();

        SiteVisit::create([
            'path' => $path,
            'url' => $validated['url'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'visitor_id_hash' => ! empty($validated['visitorId']) ? hash('sha256', $validated['visitorId']) : null,
            'session_id_hash' => ! empty($validated['sessionId']) ? hash('sha256', $validated['sessionId']) : null,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
            'occurred_at' => now(),
        ]);

        return response()->json(['success' => true], 201);
    }

    public function servicePages(): JsonResponse
    {
        $pages = ServicePage::where('status', 'published')
            ->orderByDesc('created_at')
            ->get([
                'id', 'title', 'slug', 'description', 'price', 'duration',
                'image', 'features', 'category_id', 'category_name', 'category_color',
            ]);

        return response()->json($pages);
    }

    public function dealsPages(): JsonResponse
    {
        $items = Dealspage::where('status', 1)
            ->orderByDesc('created_at')
            ->get([
                'id', 'category', 'title', 'salon', 'original_price',
                'discounted_price', 'discount', 'image', 'badge',
                'badge_color', 'description', 'valid_until', 'features',
            ]);

        return response()->json($items->map(function ($it) {
            return [
                'id' => $it->id,
                'category' => $it->category,
                'title' => $it->title,
                'salon' => $it->salon,
                'originalPrice' => $it->original_price,
                'discountedPrice' => $it->discounted_price,
                'discount' => $it->discount,
                'image' => $it->image,
                'badge' => $it->badge,
                'badgeColor' => $it->badge_color,
                'description' => $it->description,
                'validUntil' => $it->valid_until ? $it->valid_until->toDateString() : null,
                'features' => $it->features ?? [],
            ];
        })->values());
    }

    public function imageCycles(): JsonResponse
    {
        $items = ImageCycle::where('status', 1)
            ->orderBy('order')
            ->get([
                'id', 'title', 'description', 'alt', 'src_url',
                'upload_path', 'order', 'status',
            ]);

        return response()->json($items->map(function ($it) {
            return [
                'id' => $it->id,
                'title' => $it->title,
                'description' => $it->description,
                'alt' => $it->alt,
                'srcUrl' => $it->src_url,
                'uploadPath' => $it->upload_path,
                'imageUrl' => $it->image_url,
                'order' => $it->order,
                'status' => $it->status,
            ];
        })->values());
    }

    public function storeImageCycle(StoreImageCycleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! $request->hasFile('file') && empty($validated['src_url'])) {
            return response()->json([
                'error' => 'Either file or src_url is required.',
            ], 422);
        }

        $uploadPath = null;
        if ($request->hasFile('file')) {
            $uploadPath = $request->file('file')->store('image_cycles', 'public');
        }

        $item = ImageCycle::create([
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'alt' => $validated['alt'] ?? null,
            'src_url' => $validated['src_url'] ?? null,
            'upload_path' => $uploadPath,
            'order' => $validated['order'] ?? 0,
            'status' => (int) ($validated['status'] ?? 1),
            'created_by' => auth('sanctum')->id(),
            'updated_by' => auth('sanctum')->id(),
        ]);

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'alt' => $item->alt,
            'srcUrl' => $item->src_url,
            'uploadPath' => $item->upload_path,
            'imageUrl' => $item->image_url,
            'order' => $item->order,
            'status' => $item->status,
        ], 201);
    }

    public function teamMembers(): JsonResponse
    {
        $items = TeamMember::where('status', 1)
            ->orderBy('order')
            ->get([
                'id', 'name', 'role', 'bio', 'src_url', 'upload_path', 'order', 'status',
            ]);

        return response()->json($items->map(function ($it) {
            return [
                'id' => $it->id,
                'name' => $it->name,
                'role' => $it->role,
                'bio' => $it->bio,
                'src_url' => $it->src_url,
                'upload_path' => $it->upload_path,
                'image_url' => $it->image_url,
                'order' => $it->order,
                'status' => $it->status,
            ];
        })->values());
    }
}
