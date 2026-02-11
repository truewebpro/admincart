@extends('layouts.app')

@section('content')
<v-container class="container h-screen">
    <v-row justify="center" class="h-100" align="center">
        <v-col cols="12" md="4">
            <div class="position-relative">
                <v-img class="mx-auto" src="https://truewebcart.s3-accelerate.amazonaws.com/trpng.png" width="100" height="100"/>
            </div>
            <div class="text-center text-h4 font-weight-medium"><span class="text-secondary">TrueWeb</span><span class="text-red-darken-1">Cart</span></div>
            <v-card elevation="4" rounded="lg">
                <v-card-title class="bg-gray-100 d-flex">
                    {{ __('Login') }}
                </v-card-title>
                <v-card-text class="lg:p-3">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="row mb-3">
                            <label for="email" class="col-md-12 col-form-label">{{ __('Email Address') }}</label>
                            <div class="col-md-12">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="password" class="col-md-12 col-form-label">{{ __('Password') }}</label>

                            <div class="col-md-12">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 ">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <v-btn color="secondary" type="submit" class="fwbold" block>
                            {{ __('Login') }}
                        </v-btn>
                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                @if (Route::has('password.request'))
                                    <a class="btn text-primary" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>
</v-container>
@endsection
