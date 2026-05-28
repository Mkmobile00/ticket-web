<!-- ==========Newslater-Section========== -->
<footer class="footer-section">
    @include('partials.newsletter')
    <div class="container">
        <div class="footer-top">
            <div class="logo">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('assets/images/footer/footer-logo.png') }}" alt="footer">
                </a>
            </div>
            <ul class="social-icons">
                <li><a href="#0"><i class="fab fa-facebook-f"></i></a></li>
                <li><a href="#0" class="active"><i class="fab fa-twitter"></i></a></li>
                <li><a href="#0"><i class="fab fa-pinterest-p"></i></a></li>
                <li><a href="#0"><i class="fab fa-google"></i></a></li>
                <li><a href="#0"><i class="fab fa-instagram"></i></a></li>
            </ul>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-area">
                <div class="left">
                    <p>Copyright &copy; {{ date('Y') }}. All Rights Reserved By <a href="{{ route('home') }}">{{ config('app.name', 'Boleto') }}</a></p>
                </div>
                <ul class="links">
                    <li><a href="{{ route('about') }}">About</a></li>
                    <li><a href="#0">Terms Of Use</a></li>
                    <li><a href="#0">Privacy Policy</a></li>
                    <li><a href="#0">FAQ</a></li>
                    <li><a href="{{ route('contact') }}">Feedback</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<!-- ==========Newslater-Section========== -->
