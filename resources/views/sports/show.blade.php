@extends('layouts.frontend')

@section('content')
<!-- ==========Event-Banner-Section========== -->
    <section class="event-banner-section bg_img" data-background="{{ asset('assets/images/banner/banner09.jpg') }}">
        <div class="container">
            <div class="event-banner">
                <a href="https://www.youtube.com/embed/GT6-H4BRyqQ" class="video-popup">
                    <span></span>
                    <i class="flaticon-play-button"></i>
                </a>
            </div>
        </div>
    </section>
    <!-- ==========Event-Banner-Section========== -->

    <!-- ==========Event-Book-Search========== -->
    <section class="event-book-search padding-top pt-lg-0">
        <div class="container">
            <div class="event-search bg_img" data-background="{{ asset('assets/images/ticket/ticket-bg01.jpg') }}">
                <div class="event-search-top">
                    <div class="left">
                        <h3 class="title">football league tournament</h3>
                    </div>
                    <div class="right">
                        <ul class="countdown">
							<li>
								<h2><span class="days">00</span></h2>
								<p class="days_text">days</p>
							</li>
							<li>
								<h2><span class="hours">00</span></h2>
								<p class="hours_text">hrs</p>
							</li>
							<li>
								<h2><span class="minutes">00</span></h2>
								<p class="minu_text">min</p>
							</li>
							<li>
								<h2><span class="seconds">00</span></h2>
								<p class="seco_text">sec</p>
							</li>
						</ul>
                        <a href="{{ route('sports.tickets', $sport->slug) }}" class="custom-button">book tickets</a>
                    </div>
                </div>
                <div class="event-search-bottom">
                    <div class="contact-side justify-content-start">
                        <div class="item">
                            <div class="item-thumb">
                                <img src="{{ asset('assets/images/event/icon/event-icon02.png') }}" alt="event">
                            </div>
                            <div class="item-content">
                                <span class="up">17 South Sherman Street</span>
                                <span>Astoria, NY 11106</span>
                            </div>
                        </div>
                        <div class="item">
                            <div class="item-thumb">
                                <img src="{{ asset('assets/images/event/icon/event-icon03.png') }}" alt="event">
                            </div>
                            <div class="item-content">
                                <span class="up">Drop us a line:</span>
                                <a href="MailTo:hello@Boleto.com"><span class="__cf_email__" data-cfemail="6e060b0202012e2c01020b1a01400d0103">[email&#160;protected]</span></a>
                            </div>
                        </div>
                    </div>
                    <ul class="social-icons">
                        <li>
                            <a href="#0">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0" class="">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0" class="active">
                                <i class="fab fa-pinterest-p"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0" class="">
                                <i class="fab fa-google"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#0">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>    
    <!-- ==========Event-Book-Search========== -->

    <!-- ==========Event-About========== -->
    <section class="event-about padding-top padding-bottom">
        <div class="container">
            <div class="row justify-content-between flex-wrap-reverse">
                <div class="col-lg-7 col-xl-6">
                    <div class="event-about-content">
                        <div class="section-header-3 left-style m-0">
                            <span class="cate">are you ready to attend?</span>
                            <h2 class="title">football league tournament</h2>
                            <p>Morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, tristique senectus et netus et malesuada fames ac turpis egestas ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper.Aenean ultricies mi vitae est. Mauris Eonec eu ribero sit amet quam egestas semper. Aenean are ultricies mi senectus et netus et malesuada fames ac turpis egestas.
                            </p>
                            <a href="{{ route('sports.tickets', $sport->slug) }}" class="custom-button">book tickets</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-7">
                    <div class="event-about-thumb">
                        <img src="{{ asset('assets/images/sports/sports-about.jpg') }}" alt="sports">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Event-About========== -->

    <!-- ==========Statistics-Section========== -->
    <section class="statistics-section padding-top padding-bottom bg_img pb-lg-0" data-background="{{ asset('assets/images/statistics/statistics-bg01.jpg') }}">
        <div class="container">
            <div class="section-header-3">
                <span class="cate">what we have done</span>
                <h2 class="title">our recent STATISTICS</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida</p>
            </div>
            <div class="statistics-wrapper">
                <div class="row mb--20">
                    <div class="col-md-4">
                        <div class="stat-item">
                            <div class="stat-thumb">
                                <img src="{{ asset('assets/images/statistics/stat01.png') }}" alt="statistics">
                            </div>
                            <div class="stat-content">
                                <h3 class=" counter-item odometer" data-odometer-final="450"></h3>
                                <span class="info">Tickets Booked</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item">
                            <div class="stat-thumb">
                                <img src="{{ asset('assets/images/statistics/stat02.png') }}" alt="statistics">
                            </div>
                            <div class="stat-content">
                                <h3 class=" counter-item odometer" data-odometer-final="500"></h3>
                                <span class="info">Usefull Sessions</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item">
                            <div class="stat-thumb">
                                <img src="{{ asset('assets/images/statistics/stat03.png') }}" alt="statistics">
                            </div>
                            <div class="stat-content">
                                <h3 class=" counter-item odometer" data-odometer-final="28"></h3>
                                <span class="info">Talented Speakers</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Statistics-Section========== -->

    <!-- ==========Faq-Section========== -->
    <section class="faq-section padding-top">
        <div class="container">
            <div class="section-header-3">
                <span class="cate">HOW CAN WE HELP?</span>
                <h2 class="title">Frequently Asked Questions</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor  ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida</p>
            </div>
            <div class="faq-area padding-bottom">
                <div class="faq-wrapper">
                    <div class="faq-item">
                        <div class="faq-title">
                            <h6 class="title">Can I Upgrade my Tickets after Placing an Order?</h6>
                            <span class="right-icon"></span>
                        </div>
                        <div class="faq-content">
                            <p>Being that Tickto does not own any of the tickets sold on our site, we do not have the ability to exchange or replace tickets with other inventory. </p>
                            <p>If you would like to "upgrade" or change the location of your seats, you can relist your current tickets for sale here and purchase other tickets of your choice. </p>
                        </div>
                    </div>
                    <div class="faq-item active open">
                        <div class="faq-title">
                            <h6 class="title">Why did the delivery method of my tickets change?</h6>
                            <span class="right-icon"></span>
                        </div>
                        <div class="faq-content">
                            <p>Being that Tickto does not own any of the tickets sold on our site, we do not have the ability to exchange or replace tickets with other inventory. </p>
                            <p>If you would like to "upgrade" or change the location of your seats, you can relist your current tickets for sale here and purchase other tickets of your choice. </p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-title">
                            <h6 class="title">Why is there a different name printed on the tickets
                                and will this give me problems at my event?</h6>
                            <span class="right-icon"></span>
                        </div>
                        <div class="faq-content">
                            <p>Being that Tickto does not own any of the tickets sold on our site, we do not have the ability to exchange or replace tickets with other inventory. </p>
                            <p>If you would like to "upgrade" or change the location of your seats, you can relist your current tickets for sale here and purchase other tickets of your choice. </p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-title">
                            <h6 class="title">My tickets are not consecutive seats, are they still  
                                guaranteed together?</h6>
                            <span class="right-icon"></span>
                        </div>
                        <div class="faq-content">
                            <p>Being that Tickto does not own any of the tickets sold on our site, we do not have the ability to exchange or replace tickets with other inventory. </p>
                            <p>If you would like to "upgrade" or change the location of your seats, you can relist your current tickets for sale here and purchase other tickets of your choice. </p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-title">
                            <h6 class="title">Why is there a different name printed on the tickets
                                and will this give me problems at my event?</h6>
                            <span class="right-icon"></span>
                        </div>
                        <div class="faq-content">
                            <p>Being that Tickto does not own any of the tickets sold on our site, we do not have the ability to exchange or replace tickets with other inventory. </p>
                            <p>If you would like to "upgrade" or change the location of your seats, you can relist your current tickets for sale here and purchase other tickets of your choice. </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Faq-Section========== -->

    <!-- ==========Event-Section========== -->
    <section class="event-details padding-bottom padding-top">
        <div class="container">
            <div class="section-header-3">
                <span class="cate">EVENT SPONSORS</span>
                <h2 class="title">Partners & Sponsors</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor  ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida</p>
            </div>
            <div class="tabTwo sponsor-tab">
                <ul class="tab-menu">
                    <li class="active">
                        Platinum Sponsors
                    </li>
                    <li>
                        gold Sponsors
                    </li>
                    <li>
                        silver Sponsors
                    </li>
                </ul>
                <div class="tab-area">
                    <div class="tab-item active">
                        <div class="owl-theme owl-carousel sponsor-slider">
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/1.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/2.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/3.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/4.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/5.png') }}" alt="sponsor">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="tab-item">
                        <div class="owl-theme owl-carousel sponsor-slider">
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/3.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/4.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/5.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/1.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/2.png') }}" alt="sponsor">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="tab-item">
                        <div class="owl-theme owl-carousel sponsor-slider">
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/3.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/1.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/4.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/2.png') }}" alt="sponsor">
                                </a>
                            </div>
                            <div class="sponsor-thumb">
                                <a href="#0">
                                    <img src="{{ asset('assets/images/sponsor/5.png') }}" alt="sponsor">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Event-Section========== -->
@endsection

