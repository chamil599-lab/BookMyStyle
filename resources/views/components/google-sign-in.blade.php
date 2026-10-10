@props(['registration' => false])
@if (config('services.google.client_id'))
    <div class="mt-6 border-t pt-4">
        <p class="text-sm mb-3">Or continue with Google</p>
        <form id="google-session-form" method="POST" action="{{ route('auth.google') }}">
            @csrf
            <input type="hidden" name="credential" id="google-credential">
            <input type="hidden" name="role" id="google-role" value="customer">
        </form>
        <x-input-error :messages="$errors->get('credential')" />
        <div id="g_id_onload" data-client_id="{{ config('services.google.client_id') }}"
             data-callback="bookMyStyleGoogleSignIn" data-auto_prompt="false"></div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline"></div>
        <script>
            window.bookMyStyleGoogleSignIn = function (response) {
                document.getElementById('google-credential').value = response.credential;
                document.getElementById('google-role').value = document.getElementById('role')?.value || 'customer';
                document.getElementById('google-session-form').submit();
            };
        </script>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    </div>
@endif
