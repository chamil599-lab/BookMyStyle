<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book My Style</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand-mark-blue.svg') }}">
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body>

    {{ $slot }}

    @livewireScripts
</body>
</html>
