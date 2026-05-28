@extends('layouts.frontend')

@section('content')
<!-- ==========Banner-Section========== -->
    <section class="speaker-banner bg_img" data-background="{{ asset('assets/images/banner/banner07.jpg') }}">
        <div class="container">
            <div class="speaker-banner-content">
                <h2 class="title">Speaker Profile</h2>
                <ul class="breadcrumb">
                    <li>
                        <a href="{{ route('home') }}">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('events.index') }}">
                            event
                        </a>
                    </li>
                    <li>
                        speaker profile
                    </li>
                </ul>
            </div>
        </div>
    </section>
    <!-- ==========Banner-Section========== -->
    
    <!-- ==========Speaker-Single========== -->
    <section class="speaker-single padding-top pt-lg-0">
        <div class="container">
            <div class="speaker-wrapper bg-six padding-top padding-bottom">
                <div class="speaker-thumb">
                    <img src="{{ asset('assets/images/speaker/speaker05.jpg') }}" alt="speaker">
                    <a href="#0">www.website.com</a>
                </div>
                <div class="speaker-content">
                    <div class="author">
                        <h2 class="title">Kelley Valdez </h2>
                        <div class="info">Independent consultant, coach and executive coach</div>
                    </div>
                    <div class="speak-con-wrapper">
                        <div class="speak-con-area">
                            <div class="item">
                                <div class="item-thumb">
                                    <img src="{{ asset('assets/images/event/icon/event-icon03.png') }}" alt="event">
                                </div>
                                <div class="item-content">
                                    <span class="up">Drop us a line:</span>
                                    <a href="MailTo:hello@Boleto .com">hello@Boleto .com</a>
                                </div>
                            </div>
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
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <i class="fab fa-pinterest"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <i class="fab fa-behance"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="content">
                        <h3 class="subtitle">About me</h3>
                        <p>A successful marketing plan relies heavily on the pulling-power of advertising copy. Writing result-oriented ad copy is difficult, as it must appeal to, entice, and convince consumers to take action. There is no magic formula to write perfect ad copy; it is based on a number of factors, including ad placement, demographic, even the consumerâ€™s mood when they see your ad. </p>
                        <p>So how is any writer supposed to pen a stunning piece of advertising copy â€” copy that sizzles and sells? The following tips will jumpstart your creative thinking and help you write a better ad.</p>
                        <p>Consumers are inundated with ads, so itâ€™s vital that your ad catches the eye and immediately grabs interest. You could do this with a headline or slogan (such as VWâ€™s â€œDrivers Wantedâ€ campaign), color or layout (Targetâ€™s new colorful, simple ads are a testimony to this) or illustration (such as the Red Bull characters or Zoloftâ€™s depressed ball and his ladybug friend).</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Speaker-Single========== -->

    <!-- ==========Speaker-Section========== -->
    <section class="speaker-section padding-bottom padding-top">
        <div class="container">
            <div class="section-header-3">
                <span class="cate">listen to the</span>
                <h2 class="title">event speakers</h2>
                <p>World is committed to making participation in the event a harassment free experience for 
                everyone, regardless of level of experience, gender, gender identity and expression</p>
            </div>
            <div class="speaker--slider">
                <div class="speaker-slider owl-carousel owl-theme">
                    <div class="speaker-item">
                        <div class="speaker-thumb">
                            <a href="#0">
                                <img src="{{ asset('assets/images/speaker/speaker01.jpg') }}" alt="speaker">
                            </a>
                        </div>
                        <div class="speaker-content">
                            <h5 class="title">
                                <a href="#0">
                                    Gerard Bryan 
                                </a>
                            </h5>
                            <span>lead speaker</span>
                        </div>
                    </div>
                    <div class="speaker-item">
                        <div class="speaker-thumb">
                            <a href="#0">
                                <img src="{{ asset('assets/images/speaker/speaker02.jpg') }}" alt="speaker">
                            </a>
                        </div>
                        <div class="speaker-content">
                            <h5 class="title">
                                <a href="#0">
                                    Raihan Rafuj
                                </a>
                            </h5>
                            <span>lead speaker</span>
                        </div>
                    </div>
                    <div class="speaker-item">
                        <div class="speaker-thumb">
                            <a href="#0">
                                <img src="{{ asset('assets/images/speaker/speaker03.jpg') }}" alt="speaker">
                            </a>
                        </div>
                        <div class="speaker-content">
                            <h5 class="title">
                                <a href="#0">
                                    Bela Bose
                                </a>
                            </h5>
                            <span>lead speaker</span>
                        </div>
                    </div>
                    <div class="speaker-item">
                        <div class="speaker-thumb">
                            <a href="#0">
                                <img src="{{ asset('assets/images/speaker/speaker04.jpg') }}" alt="speaker">
                            </a>
                        </div>
                        <div class="speaker-content">
                            <h5 class="title">
                                <a href="#0">
                                    Grass Hopper
                                </a>
                            </h5>
                            <span>lead speaker</span>
                        </div>
                    </div>
                </div>
                <div class="speaker-prev">
                    <i class="flaticon-double-right-arrows-angles"></i>
                </div>
                <div class="speaker-next">
                    <i class="flaticon-double-right-arrows-angles"></i>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Speaker-Section========== -->
@endsection

