@php
    $isHome = request()->is('admin') || request()->is('admin/dashboard');
    $isCollection = request()->is('admin/emi-collection*');
    $isLoans = (request()->is('admin/loan*') || request()->is('admin/overdue*') || request()->is('admin/penalties*')) && !$isCollection;
    $isCustomers = request()->is('admin/customer*');
    $isMore = !$isHome && !$isLoans && !$isCustomers && !$isCollection;
@endphp

<!-- True Fixed 5-Button Mobile App Navigation Bar (Viewport Bottom) -->
<nav class="mobile-bottom-nav d-flex d-md-none">
    <!-- 1. Home -->
    <a href="{{ url('/admin') }}" class="mobile-nav-item {{ $isHome ? 'active' : '' }}">
        <i class="bi {{ $isHome ? 'bi-house-fill' : 'bi-house' }}"></i>
        <span>Home</span>
    </a>

    <!-- 2. Loans -->
    @canany(['loan_application.view', 'loan.view', 'loan_scheme.view'])
    @can('loan_application.view')
        <a href="{{ route('admin.loan-application.index') }}" class="mobile-nav-item {{ $isLoans ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i>
            <span>Loans</span>
        </a>
    @else
        <a href="{{ route('admin.loan-account.index') }}" class="mobile-nav-item {{ $isLoans ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i>
            <span>Loans</span>
        </a>
    @endcan
    @else
        <a href="{{ url('/admin') }}" class="mobile-nav-item {{ $isLoans ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i>
            <span>Loans</span>
        </a>
    @endcanany

    <!-- 3. Customers -->
    @can('customer.view')
    <a href="{{ url('/admin/customer') }}" class="mobile-nav-item {{ $isCustomers ? 'active' : '' }}">
        <i class="bi {{ $isCustomers ? 'bi-people-fill' : 'bi-people' }}"></i>
        <span>Customers</span>
    </a>
    @else
    <a href="{{ url('/admin') }}" class="mobile-nav-item {{ $isCustomers ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Customers</span>
    </a>
    @endcan

    <!-- 4. Collection -->
    @can('collection.view')
    <a href="{{ route('admin.emi-collection.index') }}" class="mobile-nav-item {{ $isCollection ? 'active' : '' }}">
        <i class="bi bi-currency-rupee"></i>
        <span>Collection</span>
    </a>
    @else
    <a href="{{ url('/admin') }}" class="mobile-nav-item {{ $isCollection ? 'active' : '' }}">
        <i class="bi bi-currency-rupee"></i>
        <span>Collection</span>
    </a>
    @endcan

    <!-- 5. More -->
    <button type="button" class="mobile-nav-item border-0 bg-transparent {{ $isMore ? 'active' : '' }}" data-bs-toggle="offcanvas" data-bs-target="#mobileAppDrawer" aria-controls="mobileAppDrawer">
        <i class="bi {{ $isMore ? 'bi-grid-3x3-gap-fill' : 'bi-grid-3x3-gap' }}"></i>
        <span>More</span>
    </button>
</nav>
