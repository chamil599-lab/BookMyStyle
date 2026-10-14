<div class="luxury-intro shell">
    <section class="luxury-hero" aria-labelledby="home-heading">
        <div class="luxury-hero-copy">
            <p class="luxury-kicker"><span></span> A little time, just for you</p>
            <h1 id="home-heading">Good hair.<br>Great skin.<br><em>Your kind of glow.</em></h1>
            <p class="luxury-description">Find a salon that feels like you. Discover beauty studios across Sri Lanka and make room for your next feel-good moment.</p>
            <div class="luxury-hero-actions">
                <a href="#salons-section" class="button luxury-primary">Find your salon <span aria-hidden="true">↗</span></a>
                <a href="{{ route('services') }}" class="luxury-text-link">Explore services <span aria-hidden="true">→</span></a>
            </div>
            <div class="luxury-proof">
                <span class="luxury-proof-mark" aria-hidden="true">✦</span>
                <p><strong>Local beauty. A personal touch.</strong><span>Browse real salons, services and customer reviews.</span></p>
            </div>
        </div>
        @php($heroImages = $images->filter(fn ($image) => \App\Support\PublicUrl::image($image->image_url))->values())
        <div class="luxury-hero-visual" x-data="{ active: 0, playing: false }" x-init="playing = ! $refs.heroFilm.paused; if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { $refs.heroFilm.pause(); playing = false; } else if ({{ $heroImages->count() }} > 1) setInterval(() => { if (playing) active = (active + 1) % {{ max(1, $heroImages->count()) }}; }, 6000)">
            <video x-ref="heroFilm" class="luxury-portrait" src="{{ asset('Girl_touching_her_hair_202605240819.mp4') }}" autoplay loop muted playsinline aria-hidden="true" @play="playing = true" @pause="playing = false"></video>
            @foreach($heroImages as $image)
                <img src="{{ \App\Support\PublicUrl::image($image->image_url) }}" alt="{{ $image->alt ?? '' }}" class="luxury-portrait hero-media" loading="eager" x-data="{ failed: false }" x-show="! failed && active === {{ $loop->index }}" x-init="failed = $el.complete && ! $el.naturalWidth" x-on:error.once="failed = true" @if(! $loop->first) x-cloak @endif>
            @endforeach
            <div class="luxury-visual-shade"></div>
            <div class="luxury-visual-top"><span>THE BEAUTY EDIT</span><span aria-hidden="true">✳</span></div>
            <div class="luxury-visual-caption"><span>Less rushing.<br>More you.</span><small>YOUR NEXT CHAPTER STARTS HERE</small></div>
            <button class="luxury-film-toggle" type="button" @click="playing ? $refs.heroFilm.pause() : $refs.heroFilm.play().catch(() => {})" :aria-label="playing ? 'Pause background video' : 'Play background video'"><span x-text="playing ? 'Pause film' : 'Play film'">Play film</span></button>
            <div class="luxury-appointment-note"><span class="luxury-note-icon" aria-hidden="true">✓</span><div><strong>Your next beauty moment</strong><span>Discover. Choose. Request.</span></div></div>
        </div>
    </section>
    <form class="luxury-discovery" method="GET" action="{{ route('salons') }}#salons-section" role="search" aria-label="Discover a salon">
        <div class="luxury-discovery-title"><span class="luxury-kicker">LET'S FIND YOUR PLACE</span><strong>What are you in the mood for?</strong></div>
        <div class="luxury-discovery-field"><label for="home-location">Salon or location</label><input id="home-location" name="q" type="search" maxlength="100" placeholder="Try a salon name or city" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="luxury-discovery-field"><label for="home-treatment">Your treatment</label><select id="home-treatment" name="department_id"><option value="">Something lovely</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
        <button class="button luxury-primary" type="submit">Find salons <span aria-hidden="true">↗</span></button>
    </form>
</div>
<section class="luxury-strip" aria-label="Discover BookMyStyle">
    <div class="shell luxury-strip-inner"><p>Beauty, on <em>your terms.</em></p><span>EXPLORE LOCAL SALONS</span><i aria-hidden="true">✦</i><span>FIND YOUR TREATMENT</span><i aria-hidden="true">✦</i><span>MAKE TIME FOR YOU</span></div>
</section>
