<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Approve BookMyStyle app</title>
<style>body{margin:0;background:#f3f7fb;color:#152c42;font:16px system-ui,sans-serif;display:grid;place-items:center;min-height:100vh}main{box-sizing:border-box;background:white;border-radius:24px;padding:32px;width:min(92vw,480px);box-shadow:0 12px 50px #17304712}h1{font-size:26px;line-height:1.2}p{line-height:1.6;color:#536779}label{display:block;font-weight:650;margin-bottom:10px}input{box-sizing:border-box;width:100%;padding:16px;border:1px solid #b7cadb;border-radius:12px;font-size:24px;text-transform:uppercase;letter-spacing:3px}button{width:100%;margin-top:20px;padding:16px;border:0;border-radius:12px;background:#2271b1;color:white;font:650 16px system-ui;cursor:pointer}.error{color:#ad2431}.brand{font-weight:750;color:#2271b1}</style></head>
<body><main><div class="brand">BookMyStyle · Administrator access</div>
@if($approved)
<h1>App request approved</h1><p>Return to your BookMyStyle app to finish signing in. You can close this page.</p>
@else
<h1>Connect your admin app</h1><p>Signed in as {{ auth()->user()->email }}.</p><p>Enter the code shown on your own BookMyStyle app. Approving grants that app access with your administrator permissions. Only approve a request you started.</p>
<form method="post" action="{{ route('mobile-admin-approval') }}">@csrf<label for="code">Code from your app</label><input id="code" name="code" value="{{ old('code') }}" maxlength="9" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="AB12-CD34" required>@error('code')<p class="error">{{ $message }}</p>@enderror<button type="submit">Approve my app</button></form>
@endif
</main></body></html>
