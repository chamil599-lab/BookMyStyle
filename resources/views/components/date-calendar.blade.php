<div x-data="{
    key(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); },
    days() { if (!/^\d{4}-\d{2}$/.test(month)) return []; let [y,m] = month.split('-').map(Number); let list = []; let first = new Date(y,m-1,1).getDay(); for(let n=0;n<first;n++) list.push({key:'blank'+n, date:null}); for(let n=1;n<=new Date(y,m,0).getDate();n++) list.push({key:this.key(new Date(y,m-1,n)),date:n}); return list; },
    move(n) { let [y,m] = month.split('-').map(Number); month = this.key(new Date(y,m-1+n,1)).slice(0,7); }
}" style="max-width: 28rem" class="space-y-3">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem">
        <button type="button" aria-label="Previous month" x-on:click="move(-1)">&lt;</button>
        <label>Month <input type="month" x-model="month" required aria-label="Calendar month"></label>
        <button type="button" aria-label="Next month" x-on:click="move(1)">&gt;</button>
    </div>
    <div role="group" aria-label="{{ $ownerCalendar ? 'Salon closed dates' : 'Appointment date' }}" style="display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:0.25rem">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)<span style="text-align:center;font-size:0.75rem">{{ $day }}</span>@endforeach
        <template x-for="day in days()" :key="day.key">
            <button type="button" x-text="day.date || ''" :aria-label="day.key + ({{ $ownerCalendar ? 'selected' : 'closed' }}.includes(day.key) ? ' - Salon closed' : '')"
                :disabled="!day.date || day.key < '{{ today()->format('Y-m-d') }}' {{ $ownerCalendar ? '' : '|| closed.includes(day.key)' }}"
                :aria-pressed="{{ $ownerCalendar ? 'selected.includes(day.key)' : 'selected === day.key' }}"
                :style="'min-height:2.5rem;border-radius:0.4rem;border:1px solid #9ca3af;' + (!day.date ? 'visibility:hidden;' : '') + (day.key < '{{ today()->format('Y-m-d') }}' ? 'opacity:0.35;' : '') + ({{ $ownerCalendar ? 'selected' : 'closed' }}.includes(day.key) ? 'background:#fee2e2;color:#991b1b;' : ({{ $ownerCalendar ? 'false' : 'selected === day.key' }} ? 'background:#2563eb;color:white;' : ''))"
                x-on:click="{{ $ownerCalendar ? 'selected = selected.includes(day.key) ? selected.filter(d => d !== day.key) : [...selected, day.key].sort()' : 'selected = day.key' }}"></button>
        </template>
    </div>
    @unless($ownerCalendar)<p class="muted text-sm">Red dates: salon closed. <span x-text="selected ? 'Selected: ' + selected : 'Select an appointment date.'"></span></p>@endunless
</div>
