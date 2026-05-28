@php
    $current = request()->route()?->getName();
    $tabs = [
        ['label' => 'Dashboard', 'route' => 'account.dashboard', 'icon' => 'fas fa-th-large'],
        ['label' => 'My Bookings', 'route' => 'account.bookings.index', 'icon' => 'fas fa-ticket-alt'],
        ['label' => 'Profile', 'route' => 'account.profile.edit', 'icon' => 'fas fa-user-cog'],
    ];
@endphp
<div class="account-nav-tabs" style="background:#1c1d28;padding:14px 20px;border-radius:6px;margin-bottom:30px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach ($tabs as $tab)
            @php
                $active = str_starts_with((string) $current, $tab['route']) || $current === $tab['route'];
            @endphp
            <a href="{{ route($tab['route']) }}"
               style="padding:8px 16px;border-radius:4px;color:{{ $active ? '#fff' : '#cfd2d6' }};background:{{ $active ? '#ff5046' : 'transparent' }};text-decoration:none;font-size:14px;">
                <i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
    <div style="color:#9ca3af;font-size:13px;">
        <i class="fas fa-user-circle"></i> {{ auth()->user()->name }}
        <form action="{{ route('logout') }}" method="POST" style="display:inline;margin-left:12px;">@csrf
            <button type="submit" style="background:transparent;border:1px solid #3a3d4a;color:#cfd2d6;padding:4px 12px;border-radius:4px;font-size:12px;cursor:pointer;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</div>
