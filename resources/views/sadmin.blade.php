<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TruewebCart') }}</title>
    <script>
        window.APP_SHOP = @json(session('shop'));
        window.APP_USER = @json(auth()->user());
    </script>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:400,500,700,900" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/sass/app.scss'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css" />
</head>
<body>
<v-app id="app" v-cloak>
</v-app>
@vite(['resources/js/vapp.js'])
<script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</body>
</html>

