<div class="newslater-section padding-bottom">
    <div class="container">
        <div class="newslater-container bg_img" data-background="{{ asset('assets/images/newslater/newslater-bg01.jpg') }}">
            <div class="newslater-wrapper">
                <h5 class="cate">subscribe to {{ config('app.name', 'Boleto') }}</h5>
                <h3 class="title">to get exclusive benifits</h3>
                <form class="newslater-form" action="{{ route('newsletter.subscribe') }}" method="POST">
                    @csrf
                    <input type="email" name="email" placeholder="Your Email Address" required>
                    <button type="submit">subscribe</button>
                </form>
                @if (session('newsletter_success'))
                    <p class="text-success">{{ session('newsletter_success') }}</p>
                @else
                    <p>We respect your privacy, so we never share your info</p>
                @endif
            </div>
        </div>
    </div>
</div>
