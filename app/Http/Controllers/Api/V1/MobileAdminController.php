<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Booking;
use App\Models\Category;
use App\Models\DealsPage;
use App\Models\Department;
use App\Models\FeaturedSalon;
use App\Models\ImageCycle;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteVisit;
use App\Models\SubscriptionPackage;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\AccountDeletion;
use App\Services\PackageManagement;
use App\Services\SubscriptionService;
use App\Services\UserModeration;
use App\Support\BookingValue;
use App\Support\ManagementAccess;
use App\Support\SubscriptionSummary;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobileAdminController extends MobileOwnerController
{
    protected function actor(Request $request): User
    {
        $actor = $request->user()?->fresh();
        abort_unless(ManagementAccess::admin($actor), 403, 'A verified administrator account is required.');

        return $actor;
    }

    protected function active(Request $request): User
    {
        return $this->actor($request);
    }

    protected function salonQuery(User $actor)
    {
        return Salon::query();
    }

    protected function serviceQuery(User $actor)
    {
        return Service::query();
    }

    protected function calendarAllowed(User $actor): bool
    {
        return true;
    }

    protected function analyticsAllowed(User $actor): bool
    {
        return true;
    }

    public function overview(Request $request)
    {
        $this->actor($request);
        $visits = SiteVisit::query();
        $subscriptions = UserSubscription::whereIn('status', ['active', 'expired', 'canceled']);
        $monthly = (clone $subscriptions)->where('starts_at', '>=', now()->startOfMonth()->subMonthsNoOverflow(11))->where('starts_at', '<', now()->startOfMonth()->addMonth())
            ->toBase()->selectRaw('SUBSTR(starts_at, 1, 7) as period, SUM(amount_paid) as value')->groupByRaw('SUBSTR(starts_at, 1, 7)')->pluck('value', 'period');
        $labels = $values = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonthsNoOverflow($i);
            $labels[] = $date->format('M Y');
            $values[] = (float) ($monthly[$date->format('Y-m')] ?? 0);
        }

        return response()->json(['data' => [
            'owners' => User::role('owner')->count(), 'pending_owners' => User::role('owner')->where('is_approved', false)->count(),
            'salons' => Salon::whereNull('deleted_at')->count(), 'services' => Service::count(), 'bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(), 'today_bookings' => Booking::whereDate('date', today())->count(),
            'booking_value' => BookingValue::total(BookingValue::query()), 'subscription_revenue' => (float) (clone $subscriptions)->sum('amount_paid'),
            'subscriptions' => app(SubscriptionSummary::class)->counts(), 'subscription_chart' => compact('labels') + ['data' => $values],
            'booking_chart' => BookingValue::chart(BookingValue::query(), today()->subDays(29), today()),
            'visits' => (clone $visits)->count(), 'today_visits' => (clone $visits)->whereDate('occurred_at', today())->count(),
            'month_visits' => (clone $visits)->where('occurred_at', '>=', now()->startOfMonth())->where('occurred_at', '<', now()->startOfMonth()->addMonth())->count(),
            'unique_visitors' => (clone $visits)->whereNotNull('visitor_id_hash')->distinct()->count('visitor_id_hash'),
            'top_packages' => UserSubscription::active()->select('package_id')->selectRaw('COUNT(*) as subscription_count, SUM(amount_paid) as payment_total')->groupBy('package_id')->orderByDesc('subscription_count')->limit(5)->with('package')->get()->map(fn ($s) => ['name' => $s->package?->name, 'count' => $s->subscription_count, 'revenue' => $s->payment_total]),
        ]]);
    }

    public function lookups(Request $request)
    {
        $data = parent::lookups($request)->getData(true)['data'];
        $data['owners'] = User::role('owner')->where('is_approved', true)->orderBy('name')->get(['id', 'name', 'email']);

        return response()->json(['data' => $data]);
    }

    private function config(string $kind): array
    {
        $configs = [
            'users' => [User::class, 'Users', [
                'name' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'nullable|string|min:8|max:255',
                'role' => 'required|in:admin,owner,user,customer', 'is_approved' => 'required|boolean',
            ]],
            'packages' => [SubscriptionPackage::class, 'Subscription packages', [
                'name' => 'required|string|max:255', 'slug' => 'required|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'price' => 'required|numeric|min:0|max:999999.99', 'duration_days' => 'required|integer|between:1,36500',
                'max_salons' => 'required|integer|between:1,1000000', 'max_staff' => 'required|integer|between:1,1000000',
                'max_services' => 'required|integer|between:1,1000000', 'max_bookings_per_month' => 'required|integer|between:1,1000000',
                'order_column' => 'required|integer|between:0,1000000', 'analytics' => 'required|boolean', 'calendar' => 'required|boolean',
                'priority_support' => 'required|boolean', 'is_active' => 'required|boolean', 'is_popular' => 'required|boolean',
                'start_date' => 'nullable|date_format:Y-m-d', 'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date', 'features' => 'nullable|array|max:50',
            ]],
            'departments' => [Department::class, 'Departments', ['name' => 'required|string|max:255', 'slug' => 'required|string|max:255', 'meta_title' => 'nullable|string|max:255', 'meta_description' => 'nullable|string|max:2000', 'is_active' => 'required|boolean']],
            'categories' => [Category::class, 'Categories', ['name' => 'required|string|max:255', 'department_id' => 'required|integer|exists:departments,id', 'parent_id' => 'nullable|integer|exists:categories,id', 'is_active' => 'required|boolean']],
            'featured-salons' => [FeaturedSalon::class, 'Featured salons', ['salon_id' => 'required|integer|exists:salons,id', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after:starts_at', 'active' => 'required|boolean']],
            'image-cycles' => [ImageCycle::class, 'Home banners', ['title' => 'nullable|string|max:255', 'description' => 'nullable|string|max:20000', 'alt' => 'nullable|string|max:255', 'src_url' => 'nullable|url:http,https|max:2048', 'order' => 'required|integer|min:0|max:1000000', 'status' => 'required|integer|in:0,1']],
            'team-members' => [TeamMember::class, 'Website team', ['name' => 'required|string|max:255', 'role' => 'nullable|string|max:255', 'bio' => 'nullable|string|max:20000', 'src_url' => 'nullable|url:http,https|max:2048', 'order' => 'required|integer|min:0|max:1000000', 'status' => 'required|boolean']],
            'service-pages' => [ServicePage::class, 'Service pages', ['title' => 'required|string|max:255', 'slug' => 'nullable|string|max:255', 'description' => 'nullable|string|max:20000', 'price' => 'nullable|string|max:255', 'duration' => 'nullable|string|max:255', 'src_url' => 'nullable|url:http,https|max:2048', 'features' => 'nullable|array|max:50', 'category_id' => 'nullable|string|max:255', 'category_name' => 'nullable|string|max:255', 'category_color' => 'nullable|string|max:50', 'status' => 'required|in:draft,published']],
            'deals' => [DealsPage::class, 'Deals', ['title' => 'required|string|max:255', 'slug' => 'nullable|string|max:255', 'category' => 'nullable|string|max:255', 'salon' => 'nullable|string|max:255', 'original_price' => 'nullable|integer|min:0|max:99999999', 'discounted_price' => 'nullable|integer|min:0|max:99999999', 'discount' => 'nullable|integer|between:0,100', 'badge' => 'nullable|string|max:255', 'badge_color' => 'nullable|string|max:50', 'description' => 'nullable|string|max:20000', 'src_url' => 'nullable|url:http,https|max:2048', 'valid_until' => 'nullable|date_format:Y-m-d', 'features' => 'nullable|array|max:50', 'status' => 'required|integer|in:0,1']],
        ];
        abort_unless(isset($configs[$kind]), 404);

        return $configs[$kind];
    }

    private function soft(string $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    private function version($record): string
    {
        return hash('sha256', json_encode($record->getAttributes()));
    }

    private function current(Request $request, $record): void
    {
        $request->validate(['version' => 'required|string|size:64']);
        abort_unless(hash_equals($this->version($record), $request->input('version')), 409, 'This record changed. Refresh before continuing.');
    }

    private function payload(string $kind, $record, User $actor): array
    {
        [$model, $label, $rules] = $this->config($kind);
        $data = $record->only(array_diff(array_keys($rules), ['password', 'role']));
        if ($kind === 'users') {
            $data['role'] = $record->getRoleNames()->first();
        }
        if ($kind === 'team-members') {
            $data['role'] = $record->role;
        }
        if ($kind === 'featured-salons') {
            $data['salon_name'] = $record->salon?->name;
        }
        $data['created_at'] = $record->created_at?->toIso8601String();
        $data['updated_at'] = $record->updated_at?->toIso8601String();
        if ($kind === 'users') {
            $data['email_verified_at'] = $record->email_verified_at?->toIso8601String();
            $data['approved_at'] = $record->approved_at?->toIso8601String();
            $data['approved_by_name'] = $record->approvedBy?->name;
        }
        $photo = $this->photoField($kind);

        return $data + ['id' => $record->id, 'version' => $this->version($record), 'deleted_at' => $record->getAttribute('deleted_at'),
            'photo_url' => $photo && $record->$photo ? Storage::disk('public')->url($record->$photo) : null,
            'actions' => ['edit' => Gate::forUser($actor)->allows('update', $record), 'delete' => Gate::forUser($actor)->allows('delete', $record),
                'restore' => $this->soft($model) && Gate::forUser($actor)->allows('restore', $record),
                'force_delete' => $this->soft($model) && Gate::forUser($actor)->allows('forceDelete', $record)],
        ];
    }

    public function schema(Request $request, string $kind)
    {
        $actor = $this->actor($request);
        [$model,$title,$rules] = $this->config($kind);
        Gate::forUser($actor)->authorize('viewAny', $model);
        $fields = [];
        foreach ($rules as $key => $rule) {
            $type = str_contains($rule, 'boolean') ? 'toggle' : (str_contains($rule, 'array') ? 'tags' : (str_contains($rule, 'integer') || str_contains($rule, 'numeric') ? 'number' : 'text'));
            if ($key === 'password') {
                $type = 'password';
            }
            if (in_array($key, ['description', 'bio', 'meta_description'])) {
                $type = 'multiline';
            }
            if (in_array($key, ['start_date', 'end_date', 'valid_until'])) {
                $type = 'date';
            }
            if (in_array($key, ['starts_at', 'ends_at'])) {
                $type = 'datetime';
            }
            $options = null;
            if ($kind === 'users' && $key === 'role') {
                $options = ['admin' => 'Administrator', 'owner' => 'Owner', 'customer' => 'Customer', 'user' => 'User'];
            }
            if ($key === 'department_id') {
                $options = Department::orderBy('name')->pluck('name', 'id')->all();
            }
            if ($key === 'parent_id') {
                $options = Category::orderBy('name')->pluck('name', 'id')->all();
            }
            if ($key === 'salon_id') {
                $options = Salon::whereNull('deleted_at')->orderBy('name')->pluck('name', 'id')->all();
            }
            if ($key === 'status' && $kind === 'service-pages') {
                $options = ['draft' => 'Draft', 'published' => 'Published'];
            }
            if ($key === 'status' && in_array($kind, ['deals', 'image-cycles'])) {
                $options = [0 => 'Draft', 1 => 'Published'];
            }
            $fields[] = ['key' => $key, 'label' => ucwords(str_replace('_', ' ', $key)), 'type' => $options !== null ? 'select' : $type,
                'required' => str_starts_with($rule, 'required'), 'decimal' => str_contains($rule, 'numeric'),
                'options' => $options === null ? null : collect($options)->map(fn ($label, $value) => compact('value', 'label'))->values()];
        }

        $filters = array_values(array_filter($fields, fn ($f) => in_array($f['key'], ['is_approved', 'is_active', 'active', 'department_id', 'parent_id', 'salon_id', 'status', 'role']) && in_array($f['type'], ['toggle', 'select'])));

        return response()->json(['data' => ['title' => $title, 'fields' => $fields, 'filters' => $filters, 'soft_delete' => $this->soft($model), 'photo' => $this->photoField($kind) !== null, 'create' => Gate::forUser($actor)->allows('create', $model)]]);
    }

    public function records(Request $request, string $kind)
    {
        $actor = $this->actor($request);
        [$model, , $recordRules] = $this->config($kind);
        Gate::forUser($actor)->authorize('viewAny', $model);
        $filterKeys = array_values(array_intersect(array_keys($recordRules), ['is_approved', 'is_active', 'active', 'department_id', 'parent_id', 'salon_id', 'status', 'role']));
        $filterRules = ['q' => 'nullable|string|max:100', 'trash' => 'nullable|in:with,only', 'pending' => 'nullable|boolean', 'sort' => 'nullable|in:newest,oldest,name_asc,name_desc', 'filter' => 'nullable|array:'.implode(',', $filterKeys)];
        foreach ($filterKeys as $key) {
            $filterRules['filter.'.$key] = str_replace('required|', 'nullable|', $recordRules[$key]);
        }
        $filters = $request->validate($filterRules);
        $query = $model::query();
        if ($this->soft($model) && ($filters['trash'] ?? null)) {
            $query = $filters['trash'] === 'only' ? $query->onlyTrashed() : $query->withTrashed();
        }
        $name = in_array($kind, ['image-cycles', 'service-pages', 'deals']) ? 'title' : 'name';
        if ($kind === 'featured-salons') {
            $name = 'id';
        }
        if (! empty($filters['q']) && $name !== 'id') {
            $query->where($name, 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%');
        }
        if ($kind === 'users' && $request->boolean('pending')) {
            $query->where('is_approved', false)->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));
        }
        foreach ($filters['filter'] ?? [] as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($kind === 'users' && $key === 'role') {
                $query->whereHas('roles', fn ($q) => $q->where('name', $value)->where('guard_name', 'web'));
            } else {
                $query->where($key, $value);
            }
        }
        $sort = $filters['sort'] ?? 'newest';
        if (str_starts_with($sort, 'name_')) {
            $query->orderBy($name, $sort === 'name_asc' ? 'asc' : 'desc');
        }

        return $this->paginated($sort === 'oldest' ? $query->orderBy('id') : $query->orderByDesc('id'), $request, fn ($r) => $this->payload($kind, $r, $actor));
    }

    public function saveRecord(Request $request, string $kind, ?int $id = null)
    {
        $actor = $this->actor($request);
        [$model,,$rules] = $this->config($kind);

        return DB::transaction(function () use ($request, $actor, $kind, $id, $model, $rules) {
            $record = $id ? $model::whereKey($id)->lockForUpdate()->firstOrFail() : new $model;
            Gate::forUser($actor->fresh())->authorize($id ? 'update' : 'create', $id ? $record : $model);
            if ($id) {
                $this->current($request, $record);
            }
            if (in_array($kind, ['packages', 'departments', 'service-pages', 'deals'])) {
                $rules['slug'] = [...explode('|', $rules['slug']), Rule::unique($record->getTable(), 'slug')->ignore($id)];
            }
            if ($kind === 'users') {
                $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)];
                if (! $id) {
                    $rules['password'] = 'required|string|min:8|max:255';
                }
            }
            $rules['features.*'] = 'string|max:255';
            $data = $request->validate($rules);
            if ($kind === 'categories' && ! empty($data['parent_id'])) {
                $seen = $id ? [$id] : [];
                $parent = Category::findOrFail($data['parent_id']);
                while ($parent) {
                    abort_if(in_array($parent->id, $seen, true) || (int) $parent->department_id !== (int) $data['department_id'], 422, 'Choose a parent in the same department without a category cycle.');
                    $seen[] = $parent->id;
                    $parent = $parent->parent_id ? Category::find($parent->parent_id) : null;
                }
            }
            if ($kind === 'users') {
                $role = $data['role'];
                unset($data['role']);
                if (empty($data['password'])) {
                    unset($data['password']);
                }
                if (! $id || (bool) $record->is_approved !== (bool) $data['is_approved']) {
                    $data['approved_at'] = $data['is_approved'] ? now() : null;
                    $data['approved_by'] = $data['is_approved'] ? $actor->id : null;
                }
                $record->fill($data)->save();
                $record->syncRoles([$role]);
                if (! $id && $role === 'owner') {
                    app(SubscriptionService::class)->startRegistrationTrial($record);
                }
            } else {
                if (in_array('created_by', $record->getFillable(), true) && ! $id) {
                    $data['created_by'] = $actor->id;
                }
                if (in_array('updated_by', $record->getFillable(), true)) {
                    $data['updated_by'] = $actor->id;
                }
                $record->fill($data)->save();
            }

            return response()->json(['data' => $this->payload($kind, $record->fresh(), $actor)], $id ? 200 : 201);
        }, 3);
    }

    public function recordAction(Request $request, string $kind, int $id, string $action)
    {
        $actor = $this->actor($request);
        [$model] = $this->config($kind);
        abort_unless(in_array($action, ['delete', 'restore', 'force-delete', 'approve', 'reject']), 404);

        return DB::transaction(function () use ($request, $actor, $model, $kind, $id, $action) {
            $query = $model::query();
            if ($this->soft($model)) {
                $query->withTrashed();
            }
            $record = $query->whereKey($id)->lockForUpdate()->firstOrFail();
            $this->current($request, $record);
            if ($kind === 'users' && in_array($action, ['approve', 'reject'])) {
                if ($action === 'approve') {
                    $data = $request->validate(['role' => 'required|in:owner,customer']);
                    app(UserModeration::class)->approve($actor, $record, $data['role']);
                } else {
                    app(UserModeration::class)->reject($actor, $record);
                }
            } else {
                $ability = match ($action) {
                    'delete' => 'delete','restore' => 'restore','force-delete' => 'forceDelete',default => null
                };
                abort_unless($ability, 404);
                Gate::forUser($actor->fresh())->authorize($ability, $record);
                if ($action === 'restore') {
                    abort_unless($this->soft($model) && $record->trashed(), 422);
                    $record->restore();
                } elseif ($action === 'force-delete') {
                    abort_unless($this->soft($model) && $record->trashed(), 422);
                    $record->forceDelete();
                } elseif ($kind === 'users') {
                    app(AccountDeletion::class)->managedDelete($actor, $record);
                } elseif ($kind === 'packages') {
                    app(PackageManagement::class)->archive($actor, $record);
                } else {
                    $record->delete();
                }
            }

            return response()->json(['message' => 'Action completed.']);
        }, 3);
    }

    private function photoField(string $kind): ?string
    {
        return match ($kind) {
            'image-cycles','team-members' => 'upload_path','service-pages','deals' => 'image',default => null
        };
    }

    public function contentPhoto(Request $request, string $kind, int $id)
    {
        $actor = $this->actor($request);
        [$model] = $this->config($kind);
        $field = $this->photoField($kind);
        abort_unless($field, 404);
        $request->validate(['image' => 'required|file|image|mimes:jpg,jpeg,png,gif,webp|max:10240|dimensions:max_width=6000,max_height=6000']);
        $record = $model::findOrFail($id);
        Gate::forUser($actor)->authorize('update', $record);
        $path = $request->file('image')->store('mobile-admin/'.$kind, 'public');
        try {
            $record->update([$field => $path]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return response()->json(['data' => $this->payload($kind, $record->fresh(), $actor)]);
    }

    public function removeContentPhoto(Request $request, string $kind, int $id)
    {
        $actor = $this->actor($request);
        [$model] = $this->config($kind);
        $field = $this->photoField($kind);
        abort_unless($field, 404);
        $record = $model::findOrFail($id);
        Gate::forUser($actor)->authorize('update', $record);
        $record->update([$field => null]);

        return response()->json(['data' => $this->payload($kind, $record->fresh(), $actor)]);
    }

    public function subscriptions(Request $request)
    {
        $actor = $this->actor($request);
        $request->validate(['status' => 'nullable|in:pending,active,expired,canceled,rejected']);

        return $this->paginated(UserSubscription::with(['user', 'package'])->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->orderByDesc('id'), $request, fn ($s) => $this->subscriptionPayload($s) + ['owner_name' => $s->user?->name, 'owner_email' => $s->user?->email, 'owner_id' => $s->user_id, 'version' => $this->version($s), 'features_snapshot' => $s->features_snapshot, 'renewed_from' => $s->renewed_from, 'cancelled_at' => $s->cancelled_at?->toIso8601String(), 'created_at' => $s->created_at?->toIso8601String()]);
    }

    public function requestSubscription(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate(['owner_id' => 'required|integer|exists:users,id', 'package_id' => 'required|integer|exists:subscription_packages,id']);
        $record = app(SubscriptionService::class)->request(User::findOrFail($data['owner_id']), SubscriptionPackage::findOrFail($data['package_id']), $request->only(['payment_method', 'transaction_id']), $actor);

        return response()->json(['data' => $this->subscriptionPayload($record)], 201);
    }

    public function subscriptionAction(Request $request, int $id, string $action)
    {
        $actor = $this->actor($request);
        abort_unless(in_array($action, ['approve', 'cancel', 'reject', 'payment']), 404);

        return DB::transaction(function () use ($request, $actor, $id, $action) {
            $record = UserSubscription::findOrFail($id);
            // Follow SubscriptionService's owner-before-subscription lock order.
            User::whereKey($record->user_id)->lockForUpdate()->firstOrFail();
            $record = UserSubscription::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->current($request, $record);
            $service = app(SubscriptionService::class);
            $updated = match ($action) {
                'approve' => $service->approve($record, $actor, $request->input('amount_paid')),'cancel' => $service->cancel($record, $actor),'reject' => $service->cancel($record, $actor, true),'payment' => $service->updatePayment($record, $actor, $request->only(['payment_method', 'transaction_id']))
            };

            return response()->json(['data' => $this->subscriptionPayload($updated)]);
        }, 3);
    }
}
