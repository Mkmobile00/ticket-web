@php $cmCities = $allCities ?? \App\Models\City::orderBy('name')->get(); @endphp
<div id="city-modal" class="city-modal" aria-hidden="true">
    <div class="city-modal-overlay" onclick="closeCityModal()"></div>
    <div class="city-modal-box">
        <button type="button" class="city-modal-close" onclick="closeCityModal()" aria-label="Close">&times;</button>

        <div class="city-modal-search">
            <i class="fas fa-search"></i>
            <input type="text" id="city-search" placeholder="Search for your city" autocomplete="off">
        </div>

        <h5 class="city-modal-title">Popular Cities</h5>
        <div class="city-modal-grid" id="city-modal-grid">
            @foreach ($cmCities as $c)
                <a href="{{ route('city.set', $c->slug) }}"
                   class="city-modal-item {{ ($selectedCity ?? null)?->id === $c->id ? 'active' : '' }}"
                   data-name="{{ strtolower($c->name) }}">
                    <span class="city-ico">
                        @if ($c->icon_url)
                            <img src="{{ $c->icon_url }}" alt="{{ $c->name }}">
                        @else
                            <i class="fas fa-map-marker-alt"></i>
                        @endif
                    </span>
                    <span class="city-nm">{{ $c->name }}</span>
                </a>
            @endforeach
        </div>
        <p id="city-modal-empty" class="city-modal-empty" style="display:none;">No city found.</p>
    </div>
</div>

<style>
    .city-modal{position:fixed;inset:0;z-index:2000;display:none;}
    .city-modal.open{display:block;}
    .city-modal-overlay{position:absolute;inset:0;background:rgba(6,10,24,.7);backdrop-filter:blur(2px);}
    .city-modal-box{position:relative;max-width:760px;margin:7vh auto 0;background:#0f1733;border:1px solid #243056;
        border-radius:14px;padding:26px 28px 30px;box-shadow:0 24px 60px rgba(0,0,0,.5);}
    .city-modal-close{position:absolute;top:14px;right:16px;background:none;border:0;color:#9aa3c2;font-size:26px;line-height:1;cursor:pointer;}
    .city-modal-close:hover{color:#fff;}
    .city-modal-search{display:flex;align-items:center;gap:10px;background:#1b2547;border:1px solid #2c3a66;border-radius:10px;padding:12px 16px;margin-bottom:22px;}
    .city-modal-search i{color:#8b95b5;}
    .city-modal-search input{flex:1;background:transparent;border:0;outline:0;color:#fff;font-size:15px;}
    .city-modal-title{text-align:center;color:#cfd4db;font-size:15px;margin:0 0 18px;font-weight:600;}
    .city-modal-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;}
    .city-modal-item{display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 6px;border-radius:10px;
        color:#cfd4db;text-decoration:none;transition:background .15s,transform .1s;border:1px solid transparent;}
    .city-modal-item:hover{background:#1b2547;color:#fff;transform:translateY(-2px);}
    .city-modal-item.active{background:#1b2547;border-color:#ff5046;color:#fff;}
    .city-modal-item .city-ico{font-size:30px;color:#ff7a4d;height:46px;display:flex;align-items:center;justify-content:center;}
    .city-modal-item .city-ico img{width:44px;height:44px;object-fit:contain;}
    .city-modal-item .city-nm{font-size:13px;text-align:center;}
    .city-modal-empty{text-align:center;color:#8b95b5;margin-top:18px;}
    @media(max-width:767px){ .city-modal-grid{grid-template-columns:repeat(3,1fr);} .city-modal-box{margin:4vh 14px 0;padding:20px;} }
</style>

<script>
    window.openCityModal = function () { document.getElementById('city-modal')?.classList.add('open'); document.getElementById('city-search')?.focus(); };
    window.closeCityModal = function () { document.getElementById('city-modal')?.classList.remove('open'); };
    (function () {
        var search = document.getElementById('city-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                var any = false;
                document.querySelectorAll('#city-modal-grid .city-modal-item').forEach(function (el) {
                    var show = el.dataset.name.indexOf(q) !== -1;
                    el.style.display = show ? '' : 'none';
                    if (show) any = true;
                });
                var empty = document.getElementById('city-modal-empty');
                if (empty) empty.style.display = any ? 'none' : 'block';
            });
        }
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closeCityModal(); });
    })();
    @if (($autoOpenCity ?? false))
        window.addEventListener('DOMContentLoaded', function () { window.openCityModal(); });
    @endif
</script>
