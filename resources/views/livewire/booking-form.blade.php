<form wire:submit="submit" class="space-y-5">
    @if (session('message'))
        <p role="status" class="panel p-4 text-sm">{{ session('message') }}</p>
    @endif
    <p class="eyebrow">Your next appointment</p>
    <h2 class="text-2xl">Book at {{ $salon->name }}</h2>
    <p class="muted text-sm">Request a time below. Your appointment is confirmed only after salon approval.</p>
    @if(!$canBook)<p role="status" class="panel p-4 text-sm">Online booking is currently unavailable. Please contact the salon.</p>@endif
    <label class="label">Name <input class="field mt-2" wire:model="name" autocomplete="name" required maxlength="255"></label>
    <label class="label">Email <input class="field mt-2" wire:model="email" type="email" autocomplete="email" required maxlength="255"></label>
    <label class="label">Phone <input class="field mt-2" wire:model="phone" type="tel" autocomplete="tel" required maxlength="20"></label>
    <label class="label">Service
        <select class="field mt-2" wire:model.live="service_id" required>
            <option value="">Choose a service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}">{{ $service->title }} · {{ $service->duration ?? 30 }} min @if($salon->show_price) · LKR {{ number_format((float)$service->price, 2) }} @endif</option>
            @endforeach
        </select>
    </label>
    <label class="label">Date <input class="field mt-2" wire:model.live="date" type="date" min="{{ today()->format('Y-m-d') }}" required></label>
    <label class="label">Available time <select class="field mt-2" wire:model="time" required><option value="">Choose a time</option>@foreach($slots as $slot)<option value="{{ $slot }}">{{ $slot }}</option>@endforeach</select></label>
    <p class="muted text-xs">Choose a service and date to see times with room for the full appointment. Availability is checked again when you submit.</p>
    @if($date && $service_id && !$slots)<p class="muted text-sm">No times available for this selection. Try another date or service.</p>@endif
    @if ($errors->any())
        <ul role="alert" class="text-red-700 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <button type="submit" class="button w-full" wire:loading.attr="disabled" wire:target="submit" @disabled(!$canBook)>Request booking</button>
</form>
