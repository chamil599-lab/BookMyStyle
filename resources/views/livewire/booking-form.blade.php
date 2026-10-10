<form wire:submit="submit" class="space-y-4">
    @if (session('message'))
        <p role="status">{{ session('message') }}</p>
    @endif
    <h2>Book at {{ $salon->name }}</h2>
    <label>Name <input wire:model="name" autocomplete="name" required maxlength="255"></label>
    <label>Email <input wire:model="email" type="email" autocomplete="email" required maxlength="255"></label>
    <label>Phone <input wire:model="phone" type="tel" autocomplete="tel" required maxlength="20"></label>
    <label>Service
        <select wire:model="service_id" required>
            <option value="">Choose a service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}">{{ $service->title }}</option>
            @endforeach
        </select>
    </label>
    <label>Date <input wire:model="date" type="date" min="{{ today()->format('Y-m-d') }}" required></label>
    <label>Time <input wire:model="time" type="time" required></label>
    <p>Choose a 30-minute slot between {{ $salon->open_time?->format('H:i') ?? '09:00' }} and {{ $salon->close_time?->format('H:i') ?? '18:00' }}. The whole service must fit within these hours.</p>
    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <button type="submit" wire:loading.attr="disabled" wire:target="submit">Submit booking</button>
</form>
