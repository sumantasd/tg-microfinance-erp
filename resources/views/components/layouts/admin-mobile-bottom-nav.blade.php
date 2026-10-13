@php
    $user = auth()->user();

    $isHome = (request()->is('admin') || request()->is('admin/dashboard')) && !request()->has('duty');
    $isCollection = request()->is('admin/emi-collection*');
    $isLoan = (request()->is('admin/loan*') || request()->is('admin/overdue*') || request()->is('admin/penalties*')) && !$isCollection;
    $isMembers = request()->is('admin/customer*');
    $isMore = !$isHome && !$isLoan && !$isMembers && !$isCollection;

    // 1. Home URL
    $homeUrl = url('/admin');

    // 2. Loan Destination (Role/Permission Aware)
    if ($user && $user->can('loan.view')) {
        $loanUrl = route('admin.loan-account.index');
    } elseif ($user && $user->can('loan_application.view')) {
        $loanUrl = route('admin.loan-application.index');
    } elseif ($user && $user->can('loan_scheme.view')) {
        $loanUrl = route('admin.loan-scheme.index');
    } else {
        $loanUrl = url('/admin');
    }

    // 3. Members Destination (Role/Permission Aware)
    if ($user && $user->can('customer.view')) {
        $membersUrl = url('/admin/customer');
    } elseif ($user && $user->can('group.view')) {
        $membersUrl = route('admin.customer-group.index');
    } else {
        $membersUrl = url('/admin');
    }

    // 4. Collection Destination (Role/Permission Aware)
    if ($user && $user->can('collection.view')) {
        $collectionUrl = route('admin.emi-collection.index');
    } else {
        $collectionUrl = url('/admin');
    }
@endphp

<!-- True Fixed 5-Button Mobile App Navigation Bar: Home | Loan | Members | Collection | More -->
<nav class="mobile-bottom-nav d-flex d-md-none" id="mobileAppBottomNavigation" aria-label="Mobile Bottom Navigation">
    <!-- 1. Home -->
    <a href="{{ $homeUrl }}" class="mobile-nav-item {{ $isHome ? 'active' : '' }}" id="bottomNavHome" title="Home">
        <div class="nav-icon-pill">
            <svg class="nav-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="{{ $isHome ? 'rgba(37, 99, 235, 0.16)' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <span>Home</span>
    </a>

    <!-- 2. Loan -->
    <a href="{{ $loanUrl }}" class="mobile-nav-item {{ $isLoan ? 'active' : '' }}" id="bottomNavLoan" title="Loan Management">
        <div class="nav-icon-pill">
            <svg class="nav-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="{{ $isLoan ? 'rgba(37, 99, 235, 0.16)' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/>
                <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>
                <circle cx="17.5" cy="12.5" r="1" fill="currentColor"/>
            </svg>
        </div>
        <span>Loan</span>
    </a>

    <!-- 3. Members -->
    <a href="{{ $membersUrl }}" class="mobile-nav-item {{ $isMembers ? 'active' : '' }}" id="bottomNavMembers" title="Members / Customers">
        <div class="nav-icon-pill">
            <svg class="nav-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="{{ $isMembers ? 'rgba(37, 99, 235, 0.16)' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <span>Members</span>
    </a>

    <!-- 4. Collection (Indian Rupee with Receipt / Coins Icon) -->
    <a href="{{ $collectionUrl }}" class="mobile-nav-item {{ $isCollection ? 'active' : '' }}" id="bottomNavCollection" title="EMI Collection">
        <div class="nav-icon-pill">
            <svg class="nav-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="{{ $isCollection ? 'rgba(37, 99, 235, 0.16)' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <!-- Receipt document perimeter -->
                <path d="M4 2v20l2.5-1.5L9 22l2.5-1.5L14 22l2.5-1.5L19 22l1-1V2l-2.5 1.5L15 2l-2.5 1.5L10 2 7.5 3.5 5 2Z"/>
                <!-- Indian Rupee symbol inside receipt -->
                <path d="M8.5 7h7"/>
                <path d="M8.5 10.5h7"/>
                <path d="M8.5 7v3.5h2.5a1.75 1.75 0 0 0 0-3.5H8.5"/>
                <path d="m10.5 13.5 4 4"/>
            </svg>
        </div>
        <span>Collection</span>
    </a>

    <!-- 5. More -->
    <button type="button"
            class="mobile-nav-item border-0 bg-transparent {{ $isMore ? 'active' : '' }}"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileAppDrawer"
            aria-controls="mobileAppDrawer"
            aria-label="Open ERP Navigation Menu"
            id="bottomNavMore"
            title="More">
        <div class="nav-icon-pill">
            <svg class="nav-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="{{ $isMore ? 'rgba(37, 99, 235, 0.16)' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="7" height="7" x="3" y="3" rx="1.5"/>
                <rect width="7" height="7" x="14" y="3" rx="1.5"/>
                <rect width="7" height="7" x="14" y="14" rx="1.5"/>
                <rect width="7" height="7" x="3" y="14" rx="1.5"/>
            </svg>
        </div>
        <span>More</span>
    </button>
</nav>
