<section class="luxury-how shell" aria-labelledby="how-heading">
    <div class="luxury-section-heading"><div><p class="luxury-kicker">A BEAUTIFUL LITTLE ROUTINE</p><h2 id="how-heading">Your next appointment,<br><em>without the guesswork.</em></h2></div><p>From a fresh cut to a little self-care. Find your place, choose your treatment and send your request.</p></div>
    <div class="luxury-steps">
        @foreach ([['01', 'Find your place', 'Explore salons by location, department or customer rating. Find the one that feels right.'], ['02', 'Make it yours', 'Browse the salon’s services, prices and available times. Choose what works for you.'], ['03', 'Send your request', 'Request your appointment, then follow its status in your account while the salon confirms.']] as [$number, $heading, $copy])
            <article><span class="luxury-step-number">{{ $number }}</span><h3>{{ $heading }}</h3><p>{{ $copy }}</p></article>
        @endforeach
    </div>
</section>
<section class="luxury-owner shell" aria-labelledby="owner-heading">
    <div class="luxury-owner-ornament" aria-hidden="true">✳</div>
    <div><p class="luxury-kicker">FOR THE PEOPLE BEHIND THE BEAUTY</p><h2 id="owner-heading">Your talent deserves<br><em>to be discovered.</em></h2><p>Bring your salon to BookMyStyle. Create your owner account and start your salon’s next chapter.</p></div>
    <a href="{{ route('register') }}" class="luxury-owner-link">Grow with us <span aria-hidden="true">↗</span></a>
</section>
