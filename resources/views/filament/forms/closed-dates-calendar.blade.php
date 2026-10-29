<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ selected: $wire.entangle('{{ $getStatePath() }}'), month: '{{ today()->format('Y-m') }}', rangeStart: '', rangeEnd: '' }">
        @include('components.date-calendar', ['ownerCalendar' => true])
        <div class="mt-4 flex flex-wrap gap-2">
            <label>From <input type="date" x-model="rangeStart" min="{{ today()->format('Y-m-d') }}"></label>
            <label>To <input type="date" x-model="rangeEnd" :min="rangeStart || '{{ today()->format('Y-m-d') }}'"></label>
            <button type="button" x-on:click="if(rangeStart && rangeEnd && rangeStart >= '{{ today()->format('Y-m-d') }}' && rangeEnd >= rangeStart) { let key = d => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); let d = new Date(rangeStart + 'T12:00:00'); let next = [...selected]; while (key(d) <= rangeEnd && next.length < 730) { if (!next.includes(key(d))) next.push(key(d)); d.setDate(d.getDate()+1); } selected = next.sort(); }">Close date range</button>
        </div>
        <p class="mt-2 text-sm">Select dates to close; select them again to reopen. Highlighted dates are closed. Maximum 730 selected dates.</p>
        <p class="mt-2 text-sm" x-text="selected.length + ' closed dates selected'"></p>
    </div>
</x-dynamic-component>
