@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'Grihalaxmi Finance';
    $companyLogo = $settings->logo_url ?? null;
@endphp

<!-- True Fixed Mobile Top Header (Viewport Top) -->
<header id="mobile-app-fixed-header" class="d-flex d-md-none">
    <!-- Left: Hamburger Menu Button -->
    <button class="btn btn-light border-0 p-1.5 bg-transparent" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileAppDrawer" aria-controls="mobileAppDrawer" aria-label="Open Navigation Menu">
        <i class="bi bi-list fs-4 text-dark"></i>
    </button>

    <!-- Center: Real Grihalaxmi Logo Image -->
    <a href="{{ url('/admin') }}" class="text-decoration-none d-flex align-items-center justify-content-center mx-auto" style="max-width: 60vw;">
        @if($companyLogo)
            <img src="{{ $companyLogo }}" alt="{{ $companyName }}" style="max-height: 34px; width: auto; max-width: 170px; object-fit: contain;">
        @else
            <span class="fw-bold font-heading text-dark fs-6 text-truncate">{{ $companyName }}</span>
        @endif
    </a>

    <!-- Right: Search & Profile Avatar -->
    <div class="d-flex align-items-center gap-1.5">
        <button type="button" class="btn btn-light rounded-circle p-1.5 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#mobileSearchModal" title="Search">
            <i class="bi bi-search fs-6 text-dark"></i>
        </button>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="mobileUserProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 34px; height: 34px; font-size: 0.75rem; background-color: #2563eb !important;">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'SA' }}
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2 mt-2" aria-labelledby="mobileUserProfileDropdown">
                <li><a class="dropdown-item rounded-2 py-1.5" href="{{ route('admin.profile.show') }}"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                @can('users.view')
                    <li><a class="dropdown-item rounded-2 py-1.5" href="{{ route('admin.system.users.index') }}"><i class="bi bi-people me-2 text-primary"></i> User Management</a></li>
                @endcan
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item rounded-2 py-1.5 text-danger border-0 bg-transparent w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Staff Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
