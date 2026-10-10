<div class="panel p-6">
    @auth
        @if(auth()->user()->hasVerifiedEmail())
            <button type="button" wire:click="toggleFavorite" wire:loading.attr="disabled" class="button mb-5" aria-pressed="{{ $favorited ? 'true' : 'false' }}">{{ $favorited ? '♥ Saved · Remove from favorites' : '♡ Save to favorites' }}</button>
        @else
            <a href="{{ route('verification.notice') }}" class="text-[var(--terra)]">Verify your email to save salons and write reviews.</a>
        @endif
        @if($eligibility['can_rate'])
            <form wire:submit="submitReview" class="grid gap-4 max-w-xl">
                <div><label for="review-rating" class="label">Your rating</label><select id="review-rating" wire:model="rating" class="field" required><option value="">Choose a rating</option>@foreach(range(5, 1) as $value)<option value="{{ $value }}">{{ $value }} stars</option>@endforeach</select></div>
                <div><label for="review-text" class="label">Your experience (optional)</label><textarea id="review-text" wire:model="review" rows="4" maxlength="1000" class="field"></textarea></div>
                <p class="muted text-xs">Your email address will not be displayed with your review.</p>
                <div><button type="submit" wire:loading.attr="disabled" class="button">Submit review</button></div>
            </form>
        @else<p class="muted text-sm">{{ $eligibility['message'] }}</p>@endif
    @else
        <p class="text-sm"><a href="{{ route('login') }}" class="text-[var(--terra)] font-bold">Sign in</a> to save salons and review your booked appointments.</p>
    @endauth
    @if($errors->any())<ul role="alert" class="mt-4 text-red-700 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</div>
