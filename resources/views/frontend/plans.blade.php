@extends('layouts.app')

@section('content')
    <section class="plans-hero py-10 text-center px-2 bg-dblu text-white text-shadow-xs text-shadow-gray-400 font-bold">
        <h1 class="text-2xl lg:text-5xl">Plans</h1>
    </section>
    <section class="mains py-10">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                @foreach($plans as $plan)
                    <div class="bg-white shadow rounded flex flex-col gap-2 p-2 border-2 {{$plan->is_popular ? 'border-blue-300' : 'border-gray-200'}}">
                        <h2 class="text-xl font-bold text-dblu">{{$plan->name}}</h2>
                        <h3 class="text-2xl"><span class="font-semibold text-red-700">£{{number_format(($plan->price/100),2)}}</span> <span class="text-sm">{{$plan->interval}}</span></h3>
                        <div><span class="font-semibold">Products Limit:</span> {{$plan->features['products_limit']}}</div>
                        @if(auth()->check())
                            <v-btn href="{{asset('/settings/account')}}" color="success" variant="elevated" density="comfortable">Subscribe Now</v-btn>
                        @else
                            <v-btn href="{{route('login')}}" color="success" variant="elevated" density="comfortable">Start Trial</v-btn>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
