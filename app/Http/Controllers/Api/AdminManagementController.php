<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SalonHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
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
        $booking = app(\App\Services\BookingService::class)->changeStatus($booking, $validated['status'], $request->user());

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
        $user = app(\App\Services\UserModeration::class)->approve($request->user(), User::findOrFail($id));

        return response()->json(['message' => 'User approved', 'user' => $user]);
    }

    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Cannot delete admin users'], 403);
        }

        \Illuminate\Support\Facades\Gate::forUser(auth()->user()->fresh())->authorize('delete', $user);
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
        $validated = $request->validate(['status' => 'required|in:active,rejected,canceled', 'amount_paid' => 'nullable|numeric|min:0']);
        $sub = UserSubscription::with('package')->findOrFail($id);
        $service = app(\App\Services\SubscriptionService::class);
        $sub = $validated['status'] === 'active'
            ? $service->approve($sub, $request->user(), $validated['amount_paid'] ?? $sub->package->price)
            : $service->cancel($sub, $request->user(), $validated['status'] === 'rejected');

        return response()->json(['message' => 'Subscription updated', 'subscription' => $sub->load(['user:id,name,email', 'package:id,name,price'])]);
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
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_salons' => 'nullable|integer|min:1',
            'max_staff' => 'nullable|integer|min:1',
            'max_services' => 'nullable|integer|min:1',
            'max_bookings_per_month' => 'nullable|integer|min:1',
            'analytics' => 'boolean',
            'priority_support' => 'boolean',
            'calendar' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'order_column' => 'nullable|integer',
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
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_salons' => 'nullable|integer|min:1',
            'max_staff' => 'nullable|integer|min:1',
            'max_services' => 'nullable|integer|min:1',
            'max_bookings_per_month' => 'nullable|integer|min:1',
            'analytics' => 'boolean',
            'priority_support' => 'boolean',
            'calendar' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'order_column' => 'nullable|integer',
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

        app(\App\Services\PackageManagement::class)->archive(auth()->user(), $package);

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
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active' => 'boolean',
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
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active' => 'boolean',
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
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
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
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
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
        $slug = $base ?: 'item';
        $candidate = $slug;
        $i = 1;

        while (true) {
            $query = DB::table($table)->where('slug', $candidate);
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
}
