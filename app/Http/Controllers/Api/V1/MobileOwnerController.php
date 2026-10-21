<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CatalogueManagement;
use App\Services\SubscriptionService;
use App\Support\BookingValue;
use App\Support\ManagementAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MobileOwnerController extends Controller
{
    protected function salonQuery(User $actor)
    {
        return Salon::where('created_by', $actor->id);
    }

    protected function serviceQuery(User $actor)
    {
        return Service::where('created_by', $actor->id);
    }

    protected function calendarAllowed(User $actor): bool
    {
        return (bool) $actor->activeSubscription?->package?->calendar;
    }

    protected function analyticsAllowed(User $actor): bool
    {
        return (bool) $actor->activeSubscription?->package?->analytics;
    }

    protected function actor(Request $request): User
    {
        $user = $request->user()?->fresh();
        abort_unless(ManagementAccess::owner($user), 403, 'A verified, approved owner account is required.');

        return $user;
    }

    protected function active(Request $request): User
    {
        $user = $this->actor($request);
        abort_unless(ManagementAccess::owner($user, true), 403, 'An active subscription is required.');

        return $user;
    }

    protected function salon(Request $request, int $id): Salon
    {
        return $this->salonQuery($this->actor($request))->findOrFail($id);
    }

    protected function service(Request $request, int $id): Service
    {
        return $this->serviceQuery($this->actor($request))->findOrFail($id);
    }

    protected function booking(Request $request, int $id): Booking
    {
        $actor = $this->active($request);
        $record = Booking::whereIn('salon_id', $this->salonQuery($actor)->select('id'))->findOrFail($id);
        Gate::forUser($actor)->authorize('view', $record);

        return $record;
    }

    protected function paginated($query, Request $request, callable $map)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $page = $query->paginate(20);

        return response()->json(['data' => $page->getCollection()->map($map), 'meta' => [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => 20,
        ]]);
    }

    protected function salonPayload(Salon $salon): array
    {
        return $salon->only(['id', 'name', 'owner_name', 'slug', 'location', 'description', 'phone', 'email',
            'number_of_staff', 'show_price', 'facebook_page', 'instagram_page', 'tiktok_page']) + [
                'open_time' => $salon->open_time?->format('H:i'), 'close_time' => $salon->close_time?->format('H:i'),
                'archived' => $salon->deleted_at !== null,
                'cover_url' => $salon->cover_url, 'media' => $salon->getMedia('cover')->merge($salon->getMedia('gallery'))->map(fn ($m) => [
                    'id' => $m->id, 'collection' => $m->collection_name, 'url' => $m->getUrl(),
                ])->values(),
            ];
    }

    protected function servicePayload(Service $service): array
    {
        return $service->only(['id', 'title', 'slug', 'description', 'department_id', 'category_id', 'price', 'duration', 'status']);
    }

    protected function bookingPayload(Booking $booking): array
    {
        return $booking->only(['id', 'salon_id', 'service_id', 'name', 'email', 'phone', 'time', 'status']) + [
            'date' => $booking->date->format('Y-m-d'), 'salon_name' => $booking->salon?->name,
            'service_title' => $booking->service?->title ?? 'Archived service',
            'version' => hash('sha256', json_encode($booking->getAttributes())),
        ];
    }

    public function overview(Request $request)
    {
        $actor = $this->actor($request);
        $subscription = $actor->activeSubscription()->with('package')->first();
        $ids = $this->salonQuery($actor)->pluck('id');
        $bookings = Booking::whereIn('salon_id', $ids);

        return response()->json(['data' => [
            'account' => $actor->only(['id', 'name', 'email', 'email_verified_at', 'is_approved']),
            'capabilities' => ['manage' => (bool) $subscription, 'analytics' => (bool) $subscription?->package?->analytics,
                'calendar' => (bool) $subscription?->package?->calendar, 'create_salon' => Gate::forUser($actor)->allows('create', Salon::class)],
            'summary' => ['salons' => $this->salonQuery($actor)->count(), 'services' => Service::where('created_by', $actor->id)->count(),
                'bookings' => (clone $bookings)->count(), 'pending' => (clone $bookings)->where('status', 'pending')->count(),
                'today' => (clone $bookings)->whereDate('date', today())->count(),
                'booking_value' => BookingValue::total(BookingValue::query($ids))],
            'subscription' => $subscription ? $this->subscriptionPayload($subscription) : null,
            'today' => $subscription ? (clone $bookings)->whereDate('date', today())->with(['salon', 'service'])->orderBy('time')->limit(5)->get()->map(fn ($b) => $this->bookingPayload($b)) : [],
        ]]);
    }

    public function lookups(Request $request)
    {
        $this->actor($request);

        return response()->json(['data' => ['departments' => Department::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'department_id'])]]);
    }

    public function salons(Request $request)
    {
        $actor = $this->actor($request);
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'archived' => 'nullable|boolean']);
        $query = $this->salonQuery($actor)->when($request->boolean('archived'), fn ($q) => $q->whereNotNull('deleted_at'), fn ($q) => $q->whereNull('deleted_at'));
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('location', 'like', $term)->orWhere('owner_name', 'like', $term));
        }

        return $this->paginated($query->with('media')->orderBy('name')->orderBy('id'), $request, fn ($s) => $this->salonPayload($s));
    }

    public function saveSalon(Request $request, ?int $id = null)
    {
        $actor = $this->active($request);
        $service = app(CatalogueManagement::class);
        $record = $id ? $service->updateSalon($actor, $this->salon($request, $id), $request->all()) : $service->createSalon($actor, $request->all());

        return response()->json(['data' => $this->salonPayload($record->fresh())], $id ? 200 : 201);
    }

    public function deleteSalon(Request $request, int $id)
    {
        $actor = $this->active($request);
        $salon = $this->salon($request, $id);
        Gate::forUser($actor)->authorize('delete', $salon);
        // Do not destroy customer booking history as a side effect of a mobile tap.
        DB::transaction(function () use ($actor, $salon) {
            User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $record = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('delete', $record);
            $record->forceFill(['deleted_at' => now(), 'updated_by' => $actor->id])->save();
        });

        return response()->json(['message' => 'Salon archived. Booking history and photos are preserved.']);
    }

    public function restoreSalon(Request $request, int $id)
    {
        $actor = $this->active($request);
        $salon = $this->salon($request, $id);
        DB::transaction(function () use ($actor, $salon) {
            User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $record = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $record);
            $record->forceFill(['deleted_at' => null, 'updated_by' => $actor->id])->save();
        });

        return response()->json(['data' => $this->salonPayload($salon->fresh())]);
    }

    public function services(Request $request)
    {
        $actor = $this->actor($request);
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:draft,published', 'department_id' => 'nullable|integer|exists:departments,id']);
        $query = $this->serviceQuery($actor)->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))->when($filters['department_id'] ?? null, fn ($q, $value) => $q->where('department_id', $value));
        if (! empty($filters['q'])) {
            $query->where('title', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%');
        }

        return $this->paginated($query->orderBy('title')->orderBy('id'), $request, fn ($s) => $this->servicePayload($s));
    }

    public function saveService(Request $request, ?int $id = null)
    {
        $actor = $this->active($request);
        $service = app(CatalogueManagement::class);
        if (! $id) {
            $this->salon($request, (int) $request->input('salon_id'));
        }
        $record = $id ? $service->updateService($actor, $this->service($request, $id), $request->all()) : $service->createService($actor, $request->all());

        return response()->json(['data' => $this->servicePayload($record)], $id ? 200 : 201);
    }

    public function deleteService(Request $request, int $id)
    {
        $actor = $this->active($request);
        $record = $this->service($request, $id);
        Gate::forUser($actor)->authorize('delete', $record);
        $record->delete();

        return response()->json(['message' => 'Service archived.']);
    }

    public function bookings(Request $request)
    {
        $actor = $this->active($request);
        Gate::forUser($actor)->authorize('viewAny', Booking::class);
        $filters = $request->validate(['status' => 'nullable|in:pending,approved,rejected,completed',
            'date' => 'nullable|date_format:Y-m-d', 'salon_id' => 'nullable|integer', 'q' => 'nullable|string|max:100']);
        if (! empty($filters['salon_id'])) {
            $this->salon($request, $filters['salon_id']);
        }
        $query = Booking::whereIn('salon_id', $this->salonQuery($actor)->select('id'))->with(['salon', 'service'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->whereDate('date', $v))
            ->when($filters['salon_id'] ?? null, fn ($q, $v) => $q->where('salon_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $v).'%'))
            ->orderByDesc('date')->orderBy('time')->orderByDesc('id');

        return $this->paginated($query, $request, fn ($b) => $this->bookingPayload($b));
    }

    public function calendar(Request $request)
    {
        $actor = $this->active($request);
        abort_unless($this->calendarAllowed($actor), 403, 'The booking calendar is not included in your current plan.');
        $request->validate(['date' => 'required|date_format:Y-m-d']);

        return $this->bookings($request);
    }

    public function saveBooking(Request $request, ?int $id = null)
    {
        $actor = $this->active($request);
        $service = app(BookingService::class);
        $fields = ['salon_id', 'service_id', 'name', 'email', 'phone', 'date', 'time', 'status'];
        if ($id) {
            $record = $this->booking($request, $id);
            $request->validate(['version' => 'required|string|size:64']);
            abort_unless(hash_equals($this->bookingPayload($record)['version'], $request->input('version')), 409, 'This booking changed. Refresh before saving.');
        }
        if ($request->has('salon_id')) {
            $this->salon($request, (int) $request->input('salon_id'));
        }
        if ($request->has('service_id')) {
            $this->service($request, (int) $request->input('service_id'));
        }
        $record = $id ? $service->update($record, $request->only($fields), $actor) : $service->create($request->only($fields), $actor, true);

        // Read persisted defaults, types and column order before fingerprinting.
        // Newly inserted attributes need not match a subsequently loaded model.
        return response()->json(['data' => $this->bookingPayload($record->fresh(['salon', 'service']))], $id ? 200 : 201);
    }

    public function slots(Request $request)
    {
        $this->active($request);
        $data = $request->validate(['salon_id' => 'required|integer', 'service_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d']);

        return response()->json(['data' => app(BookingService::class)->timeSlots($this->salon($request, $data['salon_id']), $data['date'], $this->service($request, $data['service_id']))]);
    }

    public function deleteBooking(Request $request, int $id)
    {
        $actor = $this->active($request);
        $record = $this->booking($request, $id);
        $request->validate(['version' => 'required|string|size:64']);
        DB::transaction(function () use ($request, $actor, $record) {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $locked = Booking::whereKey($record->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('delete', $locked);
            abort_unless(hash_equals($this->bookingPayload($locked)['version'], $request->input('version')), 409, 'This booking changed. Refresh before deleting.');
            $locked->delete();
        });

        return response()->json(['message' => 'Appointment deleted.']);
    }

    public function media(Request $request, int $id)
    {
        $actor = $this->active($request);
        $salon = $this->salon($request, $id);
        Gate::forUser($actor)->authorize('update', $salon);
        $data = $request->validate(['collection' => ['required', Rule::in(['cover', 'gallery'])],
            'image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:1024|dimensions:max_width=6000,max_height=6000']);
        DB::transaction(function () use ($request, $data, $salon, $actor) {
            User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $record = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $record);
            abort_if($data['collection'] === 'gallery' && $record->getMedia('gallery')->count() >= 10, 422, 'The gallery can contain up to 10 images.');
            $record->addMedia($request->file('image'))->usingFileName(bin2hex(random_bytes(16)).'.'.$request->file('image')->extension())->toMediaCollection($data['collection']);
        });

        return response()->json(['data' => $this->salonPayload($salon->fresh())], 201);
    }

    public function deleteMedia(Request $request, int $id, int $media)
    {
        $actor = $this->active($request);
        $salon = $this->salon($request, $id);
        Gate::forUser($actor)->authorize('update', $salon);
        $record = $salon->media()->whereIn('collection_name', ['cover', 'gallery'])->findOrFail($media);
        $record->delete();

        return response()->json(['message' => 'Photo removed.']);
    }

    public function reorderMedia(Request $request, int $id)
    {
        $actor = $this->active($request);
        $salon = $this->salon($request, $id);
        $data = $request->validate(['ids' => 'required|array|max:10', 'ids.*' => 'required|integer|distinct']);
        DB::transaction(function () use ($actor, $salon, $data) {
            User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $record = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $record);
            $media = $record->media()->where('collection_name', 'gallery')->lockForUpdate()->get();
            abort_unless($media->pluck('id')->sort()->values()->all() === collect($data['ids'])->sort()->values()->all(), 422, 'The gallery changed. Refresh before reordering.');
            foreach ($data['ids'] as $index => $mediaId) {
                $media->firstWhere('id', $mediaId)->update(['order_column' => $index + 1]);
            }
        });

        return response()->json(['data' => $this->salonPayload($salon->fresh())]);
    }

    protected function subscriptionPayload($subscription): array
    {
        return $subscription->only(['id', 'status', 'payment_method', 'transaction_id', 'amount_paid', 'is_trial']) + [
            'starts_at' => $subscription->starts_at?->toIso8601String(), 'ends_at' => $subscription->ends_at?->toIso8601String(),
            'package' => $subscription->package?->only(['id', 'name', 'price', 'duration_days', 'max_salons', 'max_staff', 'max_services', 'max_bookings_per_month', 'analytics', 'calendar']),
        ];
    }

    public function subscriptions(Request $request)
    {
        $actor = $this->actor($request);

        return response()->json(['data' => [
            'history' => $actor->subscriptions()->with('package')->latest('id')->get()->map(fn ($s) => $this->subscriptionPayload($s)),
            'packages' => SubscriptionPackage::active()->ordered()->get()->filter(fn ($p) => $p->isAvailable())->map(fn ($p) => $p->only([
                'id', 'name', 'price', 'duration_days', 'max_salons', 'max_staff', 'max_services', 'max_bookings_per_month', 'analytics', 'calendar', 'features', 'is_popular',
            ]))->values(),
        ]]);
    }

    public function requestSubscription(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate(['package_id' => 'required|integer|exists:subscription_packages,id']);
        $record = app(SubscriptionService::class)->request($actor, SubscriptionPackage::findOrFail($data['package_id']), $request->only(['payment_method', 'transaction_id']), $actor);

        return response()->json(['data' => $this->subscriptionPayload($record)], 201);
    }

    public function reports(Request $request)
    {
        $actor = $this->active($request);
        abort_unless($this->analyticsAllowed($actor), 403, 'Analytics is not included in your current plan.');
        $filters = $request->validate(['period' => 'nullable|in:7d,30d,90d,12m,all', 'salon_id' => 'nullable|integer', 'year' => 'nullable|integer|between:1900,2100', 'month' => 'nullable|integer|between:1,12']);
        $ids = $this->salonQuery($actor)->pluck('id');
        if (! empty($filters['salon_id'])) {
            $ids = collect([$this->salon($request, $filters['salon_id'])->id]);
        }
        $period = $filters['period'] ?? '30d';
        $start = match ($period) {
            '7d' => today()->subDays(6), '90d' => today()->subDays(89), '12m' => today()->startOfMonth()->subMonthsNoOverflow(11), 'all' => null, default => today()->subDays(29)
        };
        $all = Booking::whereIn('salon_id', $ids)->whereDate('date', '<=', today())->when($start, fn ($q) => $q->whereDate('date', '>=', $start));
        $confirmed = BookingValue::query($ids)->whereDate('date', '<=', today())->when($start, fn ($q) => $q->whereDate('date', '>=', $start));
        $profit = BookingValue::query($ids)->whereYear('date', $filters['year'] ?? now()->year)->whereMonth('date', $filters['month'] ?? now()->month)->with('salon')->get();

        return response()->json(['data' => [
            'value' => BookingValue::total($confirmed), 'bookings' => (clone $all)->count(),
            'statuses' => array_replace(array_fill_keys(['pending', 'approved', 'completed', 'rejected'], 0),
                (clone $all)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all()),
            'rating' => (float) (SalonRating::whereIn('salon_id', $ids)->avg('rating') ?? 0),
            'chart' => BookingValue::chart(BookingValue::query($ids), $start ?? today()->subDays(29), today(), $period === '12m'),
            'top_services' => (clone $confirmed)->get()->groupBy('service_id')->map(fn ($g) => ['name' => $g->first()->service?->title ?? 'Archived service', 'bookings' => $g->count(), 'value' => $g->sum(fn ($b) => $b->service?->price ?? 0)])->sortByDesc('bookings')->take(5)->values(),
            'month_value' => $profit->sum(fn ($b) => $b->service?->price ?? 0),
            'by_salon' => $profit->groupBy('salon_id')->map(fn ($g) => ['name' => $g->first()->salon?->name, 'value' => $g->sum(fn ($b) => $b->service?->price ?? 0), 'bookings' => $g->count()])->values(),
            'value_note' => 'Estimated value of approved and completed appointments at current service prices; not collected payments or net profit.',
        ]]);
    }
}
