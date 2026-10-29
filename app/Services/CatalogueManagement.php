<?php

namespace App\Services;

use App\Enums\Enums\ServiceEnum;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogueManagement
{
    public function createSalon(User $actor, array $data): Salon
    {
        Gate::forUser($actor)->authorize('create', Salon::class);
        $ownerId = ManagementAccess::admin($actor) ? ($data['owner_id'] ?? null) : $actor->id;
        Validator::make(['owner_id' => $ownerId], ['owner_id' => 'required|integer|exists:users,id'])->validate();

        return DB::transaction(function () use ($actor, $data, $ownerId) {
            $owner = User::whereKey($ownerId)->lockForUpdate()->firstOrFail();
            abort_unless($owner->hasRole('owner') && $owner->is_approved, 403);
            $validated = $this->salonData($data);
            if (! ManagementAccess::admin($actor)) {
                Gate::forUser($actor->fresh())->authorize('create', Salon::class);
                $package = $this->package($owner);
                if ($owner->salons()->count() >= $package->max_salons) {
                    throw ValidationException::withMessages(['name' => 'Your plan has reached its salon limit.']);
                }
                $this->staffLimit($validated, $package);
            }

            return Salon::create($validated + ['created_by' => $owner->id, 'updated_by' => $actor->id]);
        }, 3);
    }

    public function updateSalon(User $actor, Salon $record, array $data): Salon
    {
        return DB::transaction(function () use ($actor, $record, $data) {
            $owner = User::whereKey($record->created_by)->lockForUpdate()->firstOrFail();
            $locked = Salon::whereKey($record->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $locked);
            abort_unless((int) $locked->created_by === (int) $owner->id, 409);
            $validated = $this->salonData($data);
            if (! ManagementAccess::admin($actor)) {
                $this->staffLimit($validated, $this->package($owner));
            }
            $locked->update($validated + ['updated_by' => $actor->id]);

            return $locked;
        }, 3);
    }

    public function createService(User $actor, array $data): Service
    {
        Gate::forUser($actor)->authorize('create', Service::class);
        Validator::make($data, ['salon_id' => 'required|integer|exists:salons,id'])->validate();
        $salon = Salon::findOrFail($data['salon_id']);

        return DB::transaction(function () use ($actor, $data, $salon) {
            $owner = User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $locked = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $locked);
            abort_unless((int) $locked->created_by === (int) $owner->id, 409);
            abort_unless($owner->hasRole('owner') && $owner->is_approved, 403);
            if (! ManagementAccess::admin($actor)) {
                $package = $this->package($owner);
                if (Service::where('created_by', $owner->id)->count() >= $package->max_services) {
                    throw ValidationException::withMessages(['salon_id' => 'Your plan has reached its service limit.']);
                }
            }

            return Service::create($this->serviceData($data) + ['created_by' => $owner->id, 'updated_by' => $actor->id]);
        }, 3);
    }

    public function updateService(User $actor, Service $record, array $data): Service
    {
        return DB::transaction(function () use ($actor, $record, $data) {
            User::whereKey($record->created_by)->lockForUpdate()->firstOrFail();
            $locked = Service::whereKey($record->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $locked);
            $locked->update($this->serviceData($data) + ['updated_by' => $actor->id]);

            return $locked;
        }, 3);
    }

    private function package(User $owner): Model
    {
        $subscription = $owner->subscriptions()->active()->lockForUpdate()->first();
        abort_unless($subscription?->package, 403);

        return $subscription->package;
    }

    private function staffLimit(array $data, Model $package): void
    {
        if ($data['number_of_staff'] > $package->max_staff) {
            throw ValidationException::withMessages(['number_of_staff' => 'The staff count exceeds your plan limit.']);
        }
    }

    private function salonData(array $data): array
    {
        $validated = Validator::make($data, ['name' => 'required|string|max:255', 'owner_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255', 'location' => 'nullable|string|max:255', 'map_url' => 'nullable|url:http,https|max:2048', 'description' => 'nullable|string|max:20000',
            'phone' => 'nullable|string|max:30', 'email' => 'nullable|email|max:255', 'number_of_staff' => 'required|integer|min:0|max:10000',
            'show_price' => 'required|boolean', 'open_time' => 'nullable|date_format:H:i,H:i:s',
            'close_time' => 'nullable|date_format:H:i,H:i:s|after:open_time',
            'facebook_page' => 'nullable|url:http,https|max:2048', 'instagram_page' => 'nullable|url:http,https|max:2048',
            'tiktok_page' => 'nullable|url:http,https|max:2048'])->validate();

        return Arr::except($validated, ['created_by', 'updated_by', 'owner_id']);
    }

    private function serviceData(array $data): array
    {
        return Validator::make($data, ['title' => 'required|string|max:255', 'slug' => 'required|string|max:255',
            'description' => 'required|string|max:20000', 'department_id' => 'required|integer|exists:departments,id',
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('department_id', $data['department_id'] ?? 0)],
            'price' => 'required|numeric|min:0|max:99999999.99', 'duration' => 'required|integer|min:1|max:1440',
            'status' => ['required', Rule::in(array_keys(ServiceEnum::labels()))]])->validate();
    }
}
