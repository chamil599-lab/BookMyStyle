@props(['eyebrow', 'heading', 'description'])
<section class="bms-welcome" aria-label="Dashboard overview">
    <div>
        <p class="bms-eyebrow">{{ $eyebrow }}</p>
        <h2>{{ $heading }}</h2>
        <p>{{ $description }}</p>
    </div>
    <div class="bms-welcome-date">{{ now()->format('l') }}<strong>{{ now()->format('d F Y') }}</strong></div>
</section>
