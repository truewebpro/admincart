<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon"  href="{{ asset('favicon.ico') }}" sizes="48x48" />
{{--    <link rel="icon" type="image/png" href="{{ url('favicon-32x32.png') }}" sizes="32x32" />--}}
{{--    <link rel="icon" type="image/png" href="{{ url('favicon-16x16.png') }}" sizes="16x16" />--}}
    <title>{{ config('app.name', 'Laravel') }}</title>
@if(auth()->user())
        <meta name="currenturl" content="current url {{url()->current()}}">
    @if(Route::is('login'))
        <meta name="authlogin" content="Login route my name is this">
    @endif
    @if(Route::is('register'))
        <meta name="authlogin" content="register route my name is this">
    @endif
@endif
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:400,500,700,900" rel="stylesheet" />
{{--    <link rel="dns-prefetch" href="//fonts.gstatic.com">--}}
{{--    <link rel="preconnect" href="https://fonts.googleapis.com">--}}
{{--    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>--}}
{{--    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>--}}

    <!-- Scripts -->
    @vite(['resources/sass/app.scss'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css" />
</head>
<body>
    <v-app id="app" v-cloak>
        <nav class="shadow fixed top-0 w-full bg-white">
            <div class="container mx-auto px-4">
                <div class="flex justify-between h-16 items-center">

                    <!-- Logo -->
                    <a href="{{ url('/') }}" class="text-2xl font-semibold">

                        <img src="https://truewebcart.s3-accelerate.amazonaws.com/logo_truewebcart400.png"
                             alt="{{ config('app.name', 'Laravel') }}" width="200" height="37">
                    </a>

                    <!-- Mobile Toggle -->
                    <button id="menuBtn" class="md:hidden p-2 p-1 border border-gray-200 rounded-xl cursor-pointer">
                        <span class="iconify text-2xl" data-icon="mdi-menu"></span>
                    </button>


                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-6">
                        @guest
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="hover:text-blue-600">Login</a>
                            @endif

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="hover:text-blue-600">Register</a>
                            @endif
                        @else
                            <!-- Dropdown -->
                            <div class="relative">
                                <button id="userBtn" class="hover:text-blue-600 flex items-end gap-1">
                                    {{ Auth::user()->name }}
                                    <span class="iconify text-xl" data-icon="mdi-chevron-down"/>
                                </button>

                                <div id="userMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-lg border-gray-200 shadow">
                                    <a href="{{ route('logout') }}"
                                       class="block px-4 py-2 hover:bg-gray-100"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>
                                </div>
                            </div>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        @endguest

                    </div>
                </div>

                <!-- Mobile Menu -->
                <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                    @guest
                        <a href="{{ route('login') }}" class="block">Login</a>
                        <a href="{{ route('register') }}" class="block">Register</a>
                    @else
                        <a href="#"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="block">
                            Logout
                        </a>
                    @endguest
                </div>
            </div>
        </nav>
        <main class="bg-slate-50">
            @yield('content')
        </main>
    </v-app>
    @vite(['resources/js/app.js'])
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>


</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const menuBtn = document.getElementById('menuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const userBtn = document.getElementById('userBtn');
        const userMenu = document.getElementById('userMenu');

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function () {
                mobileMenu.classList.toggle('hidden');
            });
        }

        if (userBtn && userMenu) {
            userBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });
        }

        document.addEventListener('click', function (e) {
            if (userMenu && userBtn &&
                !userBtn.contains(e.target) &&
                !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });

    });
</script>

</html>
