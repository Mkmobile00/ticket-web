@extends('layouts.frontend')

@section('content')
<!-- ==========Sports-Details-Banner========== -->
    <section class="details-banner event-details-banner hero-area bg_img seat-plan-banner style-two" data-background="{{ asset('assets/images/banner/banner10.jpg') }}">
        <div class="container">
            <div class="details-banner-wrapper">
                <div class="details-banner-content style-two">
                    <h3 class="title"><span class="d-block">football league</span> 
                        <span class="d-block">tournament</span></h3>
                    <div class="tags">
                        <span>17 South Sherman Street Astoria, NY 11106</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Sports-Details-Banner========== -->

    <!-- ==========Page-Title========== -->
    <section class="page-title bg-one">
        <div class="container">
            <div class="page-title-area">
                <div class="item md-order-1">
                    <a href="#0" class="custom-button back-button">
                        <i class="flaticon-double-right-arrows-angles"></i>back
                    </a>
                </div>
                <div class="item date-item">
                    <span class="date">MON, SEP 09 2020</span>
                </div>
                <div class="item">
                    <h5 class="title">05:00</h5>
                    <p>Mins Left</p>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Page-Title========== -->

    <!-- ==========Event-Section========== -->
    <div class="event-facility padding-bottom padding-top">
        <div class="container"><div class="section-header-3">
            <span class="cate">simple pricing</span>
            <h2 class="title">make an appointment</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida</p>
        </div>
            <div class="row justify-content-center mb-30-none">
                <div class="col-sm-6 col-md-4">
                    <div class="sports-ticket">
                        <span class="cate">best view</span>
                        <h2 class="ticket-title"><sup>$</sup>50</h2>
                        <p>Total Seats: <span>900</span></p>
                        <a href="sports-checkout.html" class="custom-button">proceed</a>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="sports-ticket">
                        <span class="cate">comfort zone</span>
                        <h2 class="ticket-title"><sup>$</sup>79</h2>
                        <p>Total Seats: <span>900</span></p>
                        <a href="sports-checkout.html" class="custom-button">proceed</a>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="sports-ticket">
                        <span class="cate">single tickets</span>
                        <h2 class="ticket-title"><sup>$</sup>99</h2>
                        <p>Total Seats: <span>900</span></p>
                        <a href="sports-checkout.html" class="custom-button">proceed</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ==========Event-Section========== -->
@endsection

