@extends('layouts.auth')

@section('content')
<!-- ==========Sign-In-Section========== -->
    <section class="account-section bg_img" data-background="{{ asset('assets/images/account/account-bg.jpg') }}">
        <div class="container">
            <div class="padding-top padding-bottom">
                <div class="account-area">
                    <div class="section-header-3">
                        <span class="cate">hello</span>
                        <h2 class="title">welcome back</h2>
                    </div>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                        </div>
                    @endif
                    <form class="account-form" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="email2">Email<span>*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter Your Email" id="email2" required>
                        </div>
                        <div class="form-group">
                            <label for="pass3">Password<span>*</span></label>
                            <input type="password" name="password" placeholder="Password" id="pass3" required>
                        </div>
                        <div class="form-group checkgroup">
                            <input type="checkbox" name="remember" id="bal2" value="1">
                            <label for="bal2">remember me</label>
                            <a href="#0" class="forget-pass">Forget Password</a>
                        </div>
                        <div class="form-group text-center">
                            <input type="submit" value="log in">
                        </div>
                    </form>
                    <div class="option">
                        Don't have an account? <a href="{{ route('register') }}">sign up now</a>
                    </div>
                    <div class="or"><span>Or</span></div>
                    <ul class="social-icons">
                        <li>
                            <a href="#0">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0" class="active">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0">
                                <i class="fab fa-google"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Sign-In-Section========== -->
@endsection

