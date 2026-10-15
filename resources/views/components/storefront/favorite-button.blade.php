@props(['salon'])
@if(auth()->user()?->hasVerifiedEmail())
    <div class="salon-card-favorite" data-favorite-url="{{ route('salon.favorite', $salon->id) }}"
        x-data="{ saved: @js((bool) $salon->is_favorited), busy: false, error: '',
            async toggle() {
                if (this.busy) return;
                this.busy = true; this.error = '';
                try {
                    const response = await fetch(this.$root.dataset.favoriteUrl, {
                        method: this.saved ? 'DELETE' : 'POST', credentials: 'same-origin', redirect: 'error',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    if (!response.ok) throw new Error('Save failed');
                    const result = await response.json();
                    this.saved = result.favorited;
                } catch (_) { this.error = 'Could not update favorites. Please try again.'; }
                finally { this.busy = false; }
            }
        }">
        <button type="button" @click="toggle()" :disabled="busy" :aria-pressed="saved ? 'true' : 'false'" :aria-label="saved ? 'Remove from favorites' : 'Save to favorites'" :title="saved ? 'Remove from favorites' : 'Save to favorites'" class="salon-card-heart">
            <svg viewBox="0 0 24 24" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
        </button>
        <p x-show="error" x-cloak x-text="error" role="alert" class="salon-card-favorite-error"></p>
    </div>
@else
    <a class="salon-card-heart salon-card-favorite" href="{{ route(auth()->check() ? 'verification.notice' : 'login') }}" aria-label="{{ auth()->check() ? 'Verify your email to save this salon' : 'Sign in to save this salon' }}" title="{{ auth()->check() ? 'Verify your email to save' : 'Sign in to save' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
    </a>
@endif
