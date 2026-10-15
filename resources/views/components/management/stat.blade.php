@props(['label', 'value', 'icon', 'description'])
<div class="bms-stat">
    <div class="bms-stat-top">
        <p class="bms-stat-label">{{ $label }}</p>
        <span class="bms-stat-icon"><x-filament::icon :icon="$icon" /></span>
    </div>
    <p class="bms-stat-value">{{ $value }}</p>
    <p class="bms-stat-meta">{{ $description }}</p>
</div>
