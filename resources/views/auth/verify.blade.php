@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

        <div class="w-full max-w-lg">

            <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">

                <!-- Header -->
                <div class="bg-gray-100 px-6 py-4 text-lg font-semibold">
                    {{ __('Verify Your Email Address') }}
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4 text-sm text-gray-700">

                    @if (session('resent'))
                        <div class="p-3 rounded-lg bg-green-100 text-green-700">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    <p>
                        {{ __('Before proceeding, please check your email for a verification link.') }}
                    </p>

                    <p>
                        {{ __('If you did not receive the email') }},
                    </p>

                    <form method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit"
                                class="text-indigo-600 hover:underline font-medium">
                            {{ __('click here to request another') }}
                        </button>
                    </form>

                </div>

            </div>

        </div>

    </div>
@endsection
