<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SalonHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminManagementController extends Controller
{
    // ── Bookings ──────────────────────────────────────────────

    public function bookings(Request $request)
    {
        $query = Booking::with(['salon:id,name', 'service:id,title,price', 'user:id,name,email'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('salon', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('date', '<=', $to);
        }

        $bookings = $query->paginate(20);

        return response()->json($bookings);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,completed,pending',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $validated['status']]);

        try {
            $fresh = $booking->fresh(['salon.owner', 'service', 'user']);
            if ($fresh && $fresh->user_id && $fresh->user) {
                $fresh->user->notify(new \App\Notifications\BookingStatusChangedNotification($fresh));
            }
            if ($fresh && $fresh->salon?->owner) {
                $fresh->salon->owner->notify(new \App\Notifications\BookingStatusChangedNotification($fresh));
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to send BookingStatusChangedNotification: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Status updated', 'booking' => $booking->load('salon:id,name', 'service:id,title')]);
    }

    // ── Salons ────────────────────────────────────────────────

    public function salons(Request $request)
    {
        $query = Salon::with('owner:id,name,email')
            ->withCount('bookings', 'services')
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $salons = $query->paginate(20);

        $salons->getCollection()->transform(function ($salon) {
            return array_merge(
                SalonHelper::formatSalonWithMedia($salon),
                [
                    'owner' => $salon->owner,
                    'bookings_count' => $salon->bookings_count,
                    'services_count' => $salon->services_count,
                ]
            );
        });

        return response()->json($salons);
    }

    // ── Users ─────────────────────────────────────────────────

    public function users(Request $request)
    {
        $query = User::with('roles:name')
            ->withCount('salons')
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->role($role);
        }

        if ($request->query('pending') === '1') {
            $query->role('owner')->where('is_approved', false);
        }

        $users = $query->paginate(20);

        // Append primary role to each user
        $users->getCollection()->transform(function ($user) {
            $user->role = $user->getRoleNames()->first();
            return $user;
        });

        return response()->json($users);
    }

    public function approveUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Cannot modify admin users'], 403);
        }

        $user->update([
            'is_approved'  => true,
            'approved_at'  => now(),
            'approved_by'  => auth()->id(),
        ]);

        if ($user->hasRole('owner') && !$user->hasActiveOrPendingSubscription()) {
            $proPackage = SubscriptionPackage::where('slug', 'pro-salon')
                            ->where('is_active', true)->first();
            if ($proPackage) {
                UserSubscription::create([
                    'user_id'    => $user->id,
                    'package_id' => $proPackage->id,
                    'status'     => 'active',
                    'amount_paid'=> 0,
                    'is_trial'   => true,
                    'starts_at'  => now(),
                    'ends_at'    => now()->addDays(90),
                ]);
            }
        }

        return response()->json(['message' => 'User approved', 'user' => $user]);
    }

    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Cannot delete admin users'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User rejected and removed']);
    }

    // ── Subscriptions ─────────────────────────────────────────

    public function subscriptions(Request $request)
    {
        $query = UserSubscription::with(['user:id,name,email', 'package:id,name,price'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $subs = $query->paginate(20);

        return response()->json($subs);
    }

    public function updateSubscriptionStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,rejected,canceled',
        ]);

        $sub = UserSubscription::with('package')->findOrFail($id);

        if ($validated['status'] === 'active') {
            if (!in_array($sub->status, ['pending', 'canceled', 'rejected', 'expired'])) {
                return response()->json(['message' => 'This subscription cannot be activated.'], 422);
            }
            $sub->update([
                'status'       => 'active',
                'starts_at'    => now(),
                'ends_at'      => now()->addDays($sub->package->duration_days),
                'amount_paid'  => $sub->package->price,
                'cancelled_at' => null,
            ]);
        } elseif ($validated['status'] === 'rejected') {
            if ($sub->status !== 'pending') {
                return response()->json(['message' => 'Only pending subscriptions can be rejected.'], 422);
            }
            $sub->update(['status' => 'rejected']);
        } elseif ($validated['status'] === 'canceled') {
            if ($sub->status !== 'active') {
                return response()->json(['message' => 'Only active subscriptions can be canceled.'], 422);
            }
            $sub->update(['status' => 'canceled', 'cancelled_at' => now()]);
        }

        return response()->json([
            'message'      => 'Subscription updated',
            'subscription' => $sub->fresh(['user:id,name,email', 'package:id,name,price']),
        ]);
    }

    public function packages()
    {
        $packages = SubscriptionPackage::withCount([
            'subscriptions as active_count' => fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now()),
        ])->orderBy('order_column')->get();

        return response()->json($packages);
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'duration_days'          => 'required|integer|min:1',
            'max_salons'             => 'nullable|integer|min:1',
            'max_staff'              => 'nullable|integer|min:1',
            'max_services'           => 'nullable|integer|min:1',
            'max_bookings_per_month' => 'nullable|integer|min:1',
            'analytics'              => 'boolean',
            'priority_support'       => 'boolean',
            'calendar'               => 'boolean',
            'is_active'              => 'boolean',
            'is_popular'             => 'boolean',
            'order_column'           => 'nullable|integer',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']), 'subscription_packages');
        $package = SubscriptionPackage::create(array_merge($validated, ['slug' => $slug]));

        return response()->json($package->loadCount([
            'subscriptions as active_count' => fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now()),
        ]), 201);
    }

    public function updatePackage(Request $request, $id)
    {
        $package = SubscriptionPackage::findOrFail($id);

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'duration_days'          => 'required|integer|min:1',
            'max_salons'             => 'nullable|integer|min:1',
            'max_staff'              => 'nullable|integer|min:1',
            'max_services'           => 'nullable|integer|min:1',
            'max_bookings_per_month' => 'nullable|integer|min:1',
            'analytics'              => 'boolean',
            'priority_support'       => 'boolean',
            'calendar'               => 'boolean',
            'is_active'              => 'boolean',
            'is_popular'             => 'boolean',
            'order_column'           => 'nullable|integer',
        ]);

        if ($package->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['name']), 'subscription_packages', $package->id);
        }

        $package->update($validated);

        return response()->json($package->loadCount([
            'subscriptions as active_count' => fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now()),
        ]));
    }

    public function destroyPackage($id)
    {
        $package = SubscriptionPackage::findOrFail($id);

        $activeCount = $package->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->count();
        if ($activeCount > 0) {
            return response()->json([
                'message' => "Cannot delete: {$activeCount} active subscription(s) are using this package.",
            ], 422);
        }

        $package->delete();

        return response()->json(['message' => 'Package deleted']);
    }

    // ── Categories ────────────────────────────────────────────

    public function categories()
    {
        return response()->json(
            Category::with('department:id,name')->orderBy('name')->get()
        );
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active'     => 'boolean',
        ]);

        return response()->json(
            Category::create($validated)->load('department:id,name'),
            201
        );
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active'     => 'boolean',
        ]);

        $category->update($validated);

        return response()->json($category->load('department:id,name'));
    }

    public function destroyCategory($id)
    {
        Category::findOrFail($id)->delete();

        return response()->json(['message' => 'Category deleted']);
    }

    // ── Departments ───────────────────────────────────────────

    public function departments()
    {
        return response()->json(
            Department::withCount('categories')->orderBy('name')->get()
        );
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active'        => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['name']), 'departments');
        }

        return response()->json(
            Department::create($validated)->loadCount('categories'),
            201
        );
    }

    public function updateDepartment(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active'        => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['name']), 'departments', $department->id);
        }

        $department->update($validated);

        return response()->json($department->loadCount('categories'));
    }

    public function destroyDepartment($id)
    {
        Department::findOrFail($id)->delete();

        return response()->json(['message' => 'Department deleted']);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function uniqueSlug(string $base, string $table, ?int $exceptId = null): string
    {
        $slug      = $base ?: 'item';
        $candidate = $slug;
        $i         = 1;

        while (true) {
            $query = DB::table($table)->where('slug', $candidate);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            if (!$query->exists()) {
                return $candidate;
            }
            $candidate = "{$slug}-{$i}";
            $i++;
        }
    }
}
