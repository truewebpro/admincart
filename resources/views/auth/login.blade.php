@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4 bg-slate-900">

        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">

                <!-- Header -->
                <div class="bg-slate-200 px-6 py-3 font-semibold text-lg">
                    {{ __('Login') }}
                </div>

                <!-- Body -->
                <div class="p-6">
                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="block mb-1 text-sm font-medium">
                                {{ __('Email Address') }}
                            </label>

                            <input id="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus
                                   autocomplete="email"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">

                            @error('email')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block mb-1 text-sm font-medium">
                                {{ __('Password') }}
                            </label>

                            <input id="password"
                                   type="password"
                                   name="password"
                                   required
                                   autocomplete="current-password"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">

                            @error('password')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="remember"
                                   id="remember"
                                   {{ old('remember') ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded">

                            <label for="remember" class="ml-2 text-sm">
                                {{ __('Remember Me') }}
                            </label>
                        </div>

                        <!-- Submit -->
                        <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-semibold transition">
                            {{ __('Login') }}
                        </button>

                        <!-- Forgot Password -->
                        @if (Route::has('password.request'))
                            <div class="text-center">
                                <a href="{{ route('password.request') }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            </div>
                        @endif

                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
