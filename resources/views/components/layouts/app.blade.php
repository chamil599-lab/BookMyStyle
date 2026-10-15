<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book My Style</title>
    <link rel="icon" type="image/png" href="{{ asset('images/site-icon.png') }}">
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body>

    {{ $slot }}

    @livewireScripts
</body>
</html>
