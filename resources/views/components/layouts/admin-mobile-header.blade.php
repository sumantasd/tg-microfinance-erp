@php
    $settings = \App\Models\WebsiteSetting::first();
    $displayName = $settings->company_name ?? config('app.name', 'Grihalaxmi Finance');
    $displayLogo = $settings->logo_url ?? asset('images/logo.png');

    $authUser = auth()->user();
    $scopedBranchId = $authUser ? $authUser->resolveScopedBranchId() : null;
    $branchName = null;
    if ($scopedBranchId) {
        $branch = \App\Models\Branch::find($scopedBranchId);
        $branchName = $branch ? $branch->name : null;
    }

    // Live duty check for logged-in user
    $isActiveDuty = false;
    if ($authUser && $authUser->employee) {
        $isActiveDuty = \App\Models\Attendance::where('employee_id', $authUser->employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->exists();
    }
@endphp

<!-- Premium Mobile App Top Header (Frosted Glass Fintech Navigation) -->
<header id="mobile-app-fixed-header" class="d-flex d-md-none">
    <div class="d-flex align-items-center gap-2">
        <!-- Tactile Slide-out Drawer Hamburger Button -->
        <button class="btn btn-sm btn-light border-0 p-2 d-flex align-items-center justify-content-center rounded-3 bg-light-subtle text-dark"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileAppDrawer"
                aria-controls="mobileAppDrawer"
                aria-label="Open Navigation Drawer"
                style="width: 38px; height: 38px;">
            <i class="bi bi-list fs-4 text-dark lh-1"></i>
        </button>

        <!-- Brand Identity / Active Branch Pill -->
        <a href="{{ url('/admin') }}" class="text-decoration-none d-flex flex-column justify-content-center text-start">
            <div class="d-flex align-items-center gap-1.5">
                @if($displayLogo)
                    <img src="{{ $displayLogo }}" alt="{{ $displayName }}" style="max-height: 28px; width: auto; max-width: 140px; object-fit: contain;">
                @else
                    <span class="fw-bold font-heading text-dark fs-6 text-truncate" style="letter-spacing: -0.3px;">{{ $displayName }}</span>
                @endif
            </div>
            @if($branchName)
                <span class="text-muted fw-semibold text-truncate d-block" style="font-size: 0.65rem; max-width: 120px; line-height: 1.1;">
                    <i class="bi bi-geo-alt-fill text-primary" style="font-size: 0.6rem;"></i> {{ $branchName }}
                </span>
            @endif
        </a>
    </div>

    <!-- Right Header Controls: Live Duty Pill, Quick Search & Profile -->
    <div class="d-flex align-items-center gap-2">
        <!-- Live Duty Status Pill (If Active) -->
        @if($isActiveDuty)
            <a href="{{ url('/admin#employeeDutyTrackerCard') }}"
               class="badge bg-success text-white rounded-pill px-2 py-1 text-decoration-none d-flex align-items-center gap-1 shadow-xs"
               title="Active On Duty - Tap to View Tracker"
               style="font-size: 0.68rem; font-family: monospace;">
                <span class="spinner-grow spinner-grow-sm text-white" role="status" style="width: 0.45rem; height: 0.45rem;"></span>
                <span>On Duty</span>
            </a>
        @endif

        <!-- Quick Search Modal Trigger -->
        <button type="button"
                class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center border-0 bg-light-subtle text-secondary"
                data-bs-toggle="modal"
                data-bs-target="#mobileSearchModal"
                aria-label="Search ERP Records"
                style="width: 36px; height: 36px;">
            <i class="bi bi-search fs-6 text-dark"></i>
        </button>

        <!-- Profile Avatar & Quick Menu -->
        <div class="dropdown">
            <button class="btn p-0 border-0 bg-transparent dropdown-toggle no-arrow d-flex align-items-center"
                    type="button"
                    id="mobileUserProfileDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="User Profile Menu">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-xs text-white"
                     style="width: 36px; height: 36px; font-size: 0.75rem; background: linear-gradient(135deg, #0a1e36 0%, #2563eb 100%);">
                    {{ $authUser ? strtoupper(substr($authUser->name, 0, 2)) : 'SA' }}
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" aria-labelledby="mobileUserProfileDropdown" style="min-width: 220px; z-index: 1060;">
                <li class="px-3 py-2 bg-light rounded-3 mb-1">
                    <strong class="d-block text-dark small text-truncate">{{ $authUser ? $authUser->name : 'Staff Admin' }}</strong>
                    <span class="badge bg-primary-subtle text-primary font-monospace rounded-pill" style="font-size: 0.68rem;">
                        {{ $authUser && $authUser->roles->first() ? $authUser->roles->first()->name : 'Staff' }}
                    </span>
                    @if($branchName)
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;"><i class="bi bi-building me-1"></i>{{ $branchName }}</small>
                    @endif
                </li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 small" href="{{ route('admin.profile.show') }}">
                        <i class="bi bi-person text-primary"></i> My Profile
                    </a>
                </li>
                @if($authUser && $authUser->employee)
                    <li>
                        <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 small" href="{{ url('/admin#employeeDutyTrackerCard') }}">
                            <i class="bi bi-clock-history text-success"></i> Duty & Live Attendance
                        </a>
                    </li>
                @endif
                @can('users.view')
                    <li>
                        <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 small" href="{{ route('admin.system.users.index') }}">
                            <i class="bi bi-people text-info"></i> User Management
                        </a>
                    </li>
                @endcan
                @can('company.edit')
                    <li>
                        <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 small" href="{{ url('/admin/company') }}">
                            <i class="bi bi-gear text-secondary"></i> System Settings
                        </a>
                    </li>
                @endcan
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item rounded-3 py-2 text-danger border-0 bg-transparent w-100 text-start d-flex align-items-center gap-2 small fw-semibold">
                            <i class="bi bi-box-arrow-right"></i> Staff Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
