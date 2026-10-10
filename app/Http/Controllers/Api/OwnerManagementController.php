<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SalonHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerManagementController extends Controller
{
    private function ownerSalonIds()
    {
        return Salon::where('created_by', auth()->id())->pluck('id');
    }

    private function ownerServiceIds()
    {
        return Service::where('created_by', auth()->id())->pluck('id');
    }

    private function activePackage(): ?SubscriptionPackage
    {
        return auth()->user()->activeSubscription()->with('package')->first()?->package;
    }

    // Returns a 422 JsonResponse if over limit, or null if allowed.
    private function checkLimit(string $resource): ?\Illuminate\Http\JsonResponse
    {
        $package = $this->activePackage();

        if (! $package) {
            return response()->json([
                'message' => 'An active subscription is required. Please subscribe to a plan first.',
            ], 403);
        }

        $userId = auth()->id();

        switch ($resource) {
            case 'salon':
                $max = $package->max_salons;
                if ($max !== null && Salon::where('created_by', $userId)->count() >= $max) {
                    return response()->json([
                        'message' => "Your {$package->name} plan allows a maximum of {$max} salon(s). Upgrade your plan to add more.",
                    ], 422);
                }
                break;

            case 'service':
                $max = $package->max_services;
                if ($max !== null && Service::where('created_by', $userId)->count() >= $max) {
                    return response()->json([
                        'message' => "Your {$package->name} plan allows a maximum of {$max} service(s). Upgrade your plan to add more.",
                    ], 422);
                }
                break;

            case 'booking':
                $max = $package->max_bookings_per_month;
                if ($max !== null) {
                    $thisMonth = Booking::whereIn('salon_id', $this->ownerSalonIds())
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->count();
                    if ($thisMonth >= $max) {
                        return response()->json([
                            'message' => "You've reached your {$package->name} plan limit of {$max} booking(s) this month. Upgrade to continue.",
                        ], 422);
                    }
                }
                break;
        }

        return null;
    }

    public function limits()
    {
        $package = $this->activePackage();
        $userId = auth()->id();

        $salonCount = Salon::where('created_by', $userId)->count();
        $serviceCount = Service::where('created_by', $userId)->count();
        $bookingCount = Booking::whereIn('salon_id', $this->ownerSalonIds())
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'salons' => ['used' => $salonCount,   'max' => $package?->max_salons],
            'services' => ['used' => $serviceCount, 'max' => $package?->max_services],
            'bookings' => ['used' => $bookingCount, 'max' => $package?->max_bookings_per_month],
            'package' => $package?->name,
        ]);
    }

    // ── Salons ────────────────────────────────────────────────

    public function salons(Request $request)
    {
        $salons = Salon::where('created_by', auth()->id())
            ->withCount('bookings', 'services')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            $salons->map(fn (Salon $salon) => array_merge(
                SalonHelper::formatSalonWithMedia($salon),
                [
                    'bookings_count' => $salon->bookings_count,
                    'services_count' => $salon->services_count,
                ]
            ))
        );
    }

    public function salon($id)
    {
        $salon = Salon::where('created_by', auth()->id())
            ->withCount('bookings', 'services')
            ->findOrFail($id);

        return response()->json(array_merge(
            SalonHelper::formatSalonWithMedia($salon),
            [
                'bookings_count' => $salon->bookings_count,
                'services_count' => $salon->services_count,
            ]
        ));
    }

    public function storeSalon(Request $request)
    {
        if ($err = $this->checkLimit('salon')) {
            return $err;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'number_of_staff' => 'nullable|integer|min:0',
            'show_price' => 'nullable|boolean',
            'facebook_page' => 'nullable|url|max:255',
            'instagram_page' => 'nullable|url|max:255',
            'tiktok_page' => 'nullable|url|max:255',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
            'gallery' => 'nullable|array|max:10',
            'gallery.*' => 'image|mimes:jpg,jpeg,png,webp|max:1024',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']), 'salons');

        $salon = Salon::create(array_merge($validated, [
            'slug' => $slug,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        $this->syncSalonMedia($request, $salon);
        $salon->loadCount('bookings', 'services');

        return response()->json(array_merge(
            SalonHelper::formatSalonWithMedia($salon->fresh()),
            [
                'bookings_count' => $salon->bookings_count,
                'services_count' => $salon->services_count,
            ]
        ), 201);
    }

    public function updateSalon(Request $request, $id)
    {
        $salon = Salon::where('created_by', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'number_of_staff' => 'nullable|integer|min:0',
            'show_price' => 'nullable|boolean',
            'facebook_page' => 'nullable|url|max:255',
            'instagram_page' => 'nullable|url|max:255',
            'tiktok_page' => 'nullable|url|max:255',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
            'gallery' => 'nullable|array|max:10',
            'gallery.*' => 'image|mimes:jpg,jpeg,png,webp|max:1024',
            'remove_cover' => 'nullable|boolean',
            'remove_gallery_ids' => 'nullable|array',
            'remove_gallery_ids.*' => 'integer',
        ]);

        if ($salon->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['name']), 'salons', $salon->id);
        }

        $salon->update(array_merge($validated, ['updated_by' => auth()->id()]));
        $this->syncSalonMedia($request, $salon);
        $salon->loadCount('bookings', 'services');

        return response()->json(array_merge(
            SalonHelper::formatSalonWithMedia($salon->fresh()),
            [
                'bookings_count' => $salon->bookings_count,
                'services_count' => $salon->services_count,
            ]
        ));
    }

    public function destroySalon($id)
    {
        $salon = Salon::where('created_by', auth()->id())->findOrFail($id);
        $salon->delete();

        return response()->json(['message' => 'Salon deleted']);
    }

    // ── Bookings ──────────────────────────────────────────────

    public function bookings(Request $request)
    {
        $salonIds = $this->ownerSalonIds();

        $query = Booking::whereIn('salon_id', $salonIds)
            ->with(['salon:id,name', 'service:id,title,price'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($salonId = $request->query('salon_id')) {
            if ($salonIds->contains((int) $salonId)) {
                $query->where('salon_id', $salonId);
            }
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('date', '<=', $to);
        }

        return response()->json($query->paginate(20));
    }

    public function storeBooking(Request $request)
    {
        if ($err = $this->checkLimit('booking')) {
            return $err;
        }

        $salonIds = $this->ownerSalonIds();
        $serviceIds = $this->ownerServiceIds();

        $validated = $request->validate([
            'salon_id' => ['required', 'integer', function ($attr, $val, $fail) use ($salonIds) {
                if (! $salonIds->contains((int) $val)) {
                    $fail('Invalid salon.');
                }
            }],
            'service_id' => ['required', 'integer', function ($attr, $val, $fail) use ($serviceIds) {
                if (! $serviceIds->contains((int) $val)) {
                    $fail('Invalid service.');
                }
            }],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'status' => 'nullable|in:pending,approved,completed,rejected',
        ]);

        $validated['status'] = $validated['status'] ?? 'pending';
        $booking = app(\App\Services\BookingService::class)->create($validated, $request->user(), true);

        return response()->json($booking->load('salon:id,name', 'service:id,title,price'), 201);
    }

    public function updateBooking(Request $request, $id)
    {
        $salonIds = $this->ownerSalonIds();
        $serviceIds = $this->ownerServiceIds();
        $booking = Booking::whereIn('salon_id', $salonIds)->findOrFail($id);

        $validated = $request->validate([
            'salon_id' => ['required', 'integer', function ($attr, $val, $fail) use ($salonIds) {
                if (! $salonIds->contains((int) $val)) {
                    $fail('Invalid salon.');
                }
            }],
            'service_id' => ['required', 'integer', function ($attr, $val, $fail) use ($serviceIds) {
                if (! $serviceIds->contains((int) $val)) {
                    $fail('Invalid service.');
                }
            }],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'status' => 'nullable|in:pending,approved,completed,rejected',
        ]);

        $validated['status'] = $validated['status'] ?? $booking->status;
        $booking = app(\App\Services\BookingService::class)->update($booking, $validated, $request->user());

        return response()->json($booking->load('salon:id,name', 'service:id,title,price'));
    }

    public function destroyBooking($id)
    {
        $salonIds = $this->ownerSalonIds();
        $booking = Booking::whereIn('salon_id', $salonIds)->findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted']);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $salonIds = $this->ownerSalonIds();
        $booking = Booking::whereIn('salon_id', $salonIds)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,completed,pending',
        ]);

        $booking = app(\App\Services\BookingService::class)->changeStatus($booking, $validated['status'], $request->user());

        return response()->json(['message' => 'Status updated', 'booking' => $booking->load('salon:id,name', 'service:id,title')]);
    }

    // ── Services ──────────────────────────────────────────────

    private function serviceBookingCountScope(): \Closure
    {
        $salonIds = $this->ownerSalonIds();

        return function ($q) use ($salonIds) {
            $q->whereIn('status', ['approved', 'completed'])
                ->whereIn('salon_id', $salonIds);
        };
    }

    public function services(Request $request)
    {
        $query = Service::where('created_by', auth()->id())
            ->with(['department:id,name', 'category:id,name'])
            ->withCount(['bookings as bookings_count' => $this->serviceBookingCountScope()])
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 1000);

        return response()->json($query->paginate($perPage));
    }

    public function storeService(Request $request)
    {
        if ($err = $this->checkLimit('service')) {
            return $err;
        }

        $validated = $request->validate([
            'title' => 'required|string|max:2000',
            'description' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'status' => 'required|in:published,unpublished',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['title']), 'services');

        $service = Service::create(array_merge($validated, [
            'slug' => $slug,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        return response()->json(
            $service->load('department:id,name', 'category:id,name')
                ->loadCount(['bookings as bookings_count' => $this->serviceBookingCountScope()]),
            201
        );
    }

    public function updateService(Request $request, $id)
    {
        $service = Service::where('created_by', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:2000',
            'description' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'status' => 'required|in:published,unpublished',
        ]);

        if ($service->title !== $validated['title']) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['title']), 'services', $service->id);
        }

        $service->update(array_merge($validated, ['updated_by' => auth()->id()]));

        return response()->json(
            $service->load('department:id,name', 'category:id,name')
                ->loadCount(['bookings as bookings_count' => $this->serviceBookingCountScope()])
        );
    }

    public function destroyService($id)
    {
        $service = Service::where('created_by', auth()->id())->findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    public function departments()
    {
        return response()->json(Department::where('is_active', true)->select('id', 'name')->get());
    }

    public function categories(Request $request)
    {
        $deptId = $request->query('department_id');

        $query = Category::where('is_active', true)->select('id', 'name');

        if ($deptId) {
            $query->where('department_id', (int) $deptId);
        }

        return response()->json($query->orderBy('name')->get());
    }

    // ── Helpers ───────────────────────────────────────────────

    private function uniqueSlug(string $base, string $table, ?int $exceptId = null): string
    {
        $slug = $base ?: 'item';
        $candidate = $slug;
        $i = 1;

        while (true) {
            $query = \DB::table($table)->where('slug', $candidate);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            if (! $query->exists()) {
                return $candidate;
            }
            $candidate = "{$slug}-{$i}";
            $i++;
        }
    }

    private function syncSalonMedia(Request $request, Salon $salon): void
    {
        if ($request->boolean('remove_cover') && ! $request->hasFile('cover')) {
            $salon->clearMediaCollection('cover');
        }

        if ($request->hasFile('cover')) {
            $salon->clearMediaCollection('cover');
            $salon->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        $removeGalleryIds = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter();

        if ($removeGalleryIds->isNotEmpty()) {
            $salon->getMedia('gallery')
                ->whereIn('id', $removeGalleryIds)
                ->each
                ->delete();
        }

        $newGalleryFiles = $request->file('gallery', []);
        $existingGalleryCount = $salon->getMedia('gallery')->count();

        if (($existingGalleryCount + count($newGalleryFiles)) > 10) {
            abort(response()->json([
                'message' => 'Gallery images cannot exceed 10 files in total.',
                'errors' => [
                    'gallery' => ['Gallery images cannot exceed 10 files in total.'],
                ],
            ], 422));
        }

        foreach ($newGalleryFiles as $file) {
            $salon->addMedia($file)->toMediaCollection('gallery');
        }
    }
}
