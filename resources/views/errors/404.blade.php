@extends('layouts.auth')

@section('content')
<!-- ==========Four-Not-Four-Section========== -->
    <section class="section-404 padding-top padding-bottom">
        <div class="container">
            <div class="thumb-404">
                <img src="{{ asset('assets/images/404.png') }}" alt="404">
            </div>
            <h3 class="title">Oops.. looks like you got lost :( </h3>
            <a href="{{ route('home') }}" class="custom-button">Back To Home <i class="flaticon-right"></i></a>
        </div>
    </section>
    <!-- ==========Four-Not-Four-Section========== -->
@endsection

