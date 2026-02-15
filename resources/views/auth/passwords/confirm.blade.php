@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

        <div class="w-full max-w-lg">

            <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">

                <!-- Header -->
                <div class="bg-gray-100 px-6 py-4 text-lg font-semibold">
                    {{ __('Confirm Password') }}
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">

                    <p class="text-sm text-gray-600">
                        {{ __('Please confirm your password before continuing.') }}
                    </p>

                    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                        @csrf

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
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">

                            @error('password')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-3">

                            <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-semibold transition">
                                {{ __('Confirm Password') }}
                            </button>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="text-center text-sm text-blue-600 hover:underline">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            @endif

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>
@endsection
