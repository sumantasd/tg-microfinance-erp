@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'Grihalaxmi Finance';
    $companyLogo = $settings->logo_url ?? null;

    $authUser = auth()->user();
    $scopedBranchId = $authUser ? $authUser->resolveScopedBranchId() : null;
    $branchName = null;
    if ($scopedBranchId) {
        $branch = \App\Models\Branch::find($scopedBranchId);
        $branchName = $branch ? $branch->name : null;
    }

    $dutyStatus = 'not_started';
    if ($authUser && $authUser->employee) {
        $todayAtt = \App\Models\Attendance::where('employee_id', $authUser->employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->latest('id')
            ->first();
        if ($todayAtt) {
            $dutyStatus = $todayAtt->isCompleted() ? 'completed' : ($todayAtt->isActive() ? 'active' : 'not_started');
        }
    }
@endphp

<!-- Premium Mobile App Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-start border-0 shadow-xl"
     tabindex="-1"
     id="mobileAppDrawer"
     aria-labelledby="mobileAppDrawerLabel"
     style="width: 310px; max-width: 88vw; z-index: 1055; background-color: #ffffff;">

    <!-- Drawer Header (Rich Deep Navy Fintech Theme) -->
    <div class="offcanvas-header text-white py-3 px-3.5 border-bottom border-white-10"
         style="background: linear-gradient(135deg, #0a1e36 0%, #0f2942 100%);">
        <div class="d-flex align-items-center gap-2.5">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="img-fluid" style="max-height: 32px; max-width: 130px; object-fit: contain;">
            @else
                <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px; background-color: #2563eb !important;">
                    <i class="bi bi-bank2 fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold font-heading text-white lh-sm" style="font-size: 0.95rem;">{{ $companyName }}</h6>
                    <small class="text-white-50 font-monospace text-uppercase" style="font-size: 0.625rem; letter-spacing: 0.6px;">Enterprise ERP Mobile</small>
                </div>
            @endif
        </div>
        <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Drawer User Profile Banner & Status Bar -->
    <div class="p-3 bg-light border-bottom">
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm text-white"
                     style="width: 44px; height: 44px; font-size: 0.875rem; background: linear-gradient(135deg, #0a1e36 0%, #2563eb 100%);">
                    {{ $authUser ? strtoupper(substr($authUser->name, 0, 2)) : 'SA' }}
                </div>
                <span class="position-absolute bottom-0 end-0 p-1 bg-{{ $dutyStatus === 'active' ? 'success' : ($dutyStatus === 'completed' ? 'primary' : 'secondary') }} border border-white rounded-circle"
                      style="width: 11px; height: 11px;"
                      title="{{ ucfirst($dutyStatus) }}"></span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-bold text-dark text-truncate lh-sm" style="font-size: 0.9rem;">
                    {{ $authUser ? $authUser->name : 'Staff Admin' }}
                </div>
                <div class="d-flex align-items-center gap-1.5 mt-0.5 flex-wrap">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 font-monospace" style="font-size: 0.65rem;">
                        {{ $authUser && $authUser->roles->first() ? $authUser->roles->first()->name : 'Staff' }}
                    </span>
                    @if($branchName)
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 text-truncate" style="font-size: 0.65rem; max-width: 130px;">
                            <i class="bi bi-geo-alt-fill text-danger me-0.5"></i>{{ $branchName }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Duty Status Quick Pill inside Drawer -->
        @if($authUser && $authUser->employee)
            <div class="mt-2.5 pt-2 border-top border-light-subtle d-flex align-items-center justify-content-between">
                <small class="text-muted fw-semibold" style="font-size: 0.72rem;">Live Duty Status:</small>
                @if($dutyStatus === 'active')
                    <a href="{{ url('/admin#employeeDutyTrackerCard') }}"
                       class="badge bg-success text-white rounded-pill px-2.5 py-1 text-decoration-none d-inline-flex align-items-center gap-1"
                       data-bs-dismiss="offcanvas"
                       style="font-size: 0.7rem;">
                        <span class="spinner-grow spinner-grow-sm text-white" role="status" style="width: 0.45rem; height: 0.45rem;"></span>
                        <span>Active On Duty</span>
                    </a>
                @elseif($dutyStatus === 'completed')
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.7rem;">
                        <i class="bi bi-check-circle-fill me-1"></i>Shift Completed
                    </span>
                @else
                    <a href="{{ url('/admin#employeeDutyTrackerCard') }}"
                       class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 text-decoration-none"
                       data-bs-dismiss="offcanvas"
                       style="font-size: 0.7rem;">
                        <i class="bi bi-circle me-1"></i>Off Duty (Tap to Check-In)
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Quick Action Shortcut Pills -->
    <div class="p-2.5 bg-white border-bottom">
        <div class="d-flex align-items-center gap-1.5 overflow-x-auto pb-1" style="scrollbar-width: none;">
            @can('loan_application.create')
                <a href="{{ route('admin.loan-application.create') }}"
                   class="btn btn-sm btn-light border rounded-pill text-nowrap px-2.5 py-1 fw-semibold text-dark shadow-2xs d-inline-flex align-items-center gap-1"
                   style="font-size: 0.72rem;">
                    <i class="bi bi-file-earmark-plus text-primary"></i> Apply Loan
                </a>
            @endcan
            @can('customer.create')
                <a href="{{ route('admin.customer.create') }}"
                   class="btn btn-sm btn-light border rounded-pill text-nowrap px-2.5 py-1 fw-semibold text-dark shadow-2xs d-inline-flex align-items-center gap-1"
                   style="font-size: 0.72rem;">
                    <i class="bi bi-person-plus text-success"></i> New Member
                </a>
            @endcan
            @can('collection.view')
                <a href="{{ route('admin.emi-collection.index') }}"
                   class="btn btn-sm btn-light border rounded-pill text-nowrap px-2.5 py-1 fw-semibold text-dark shadow-2xs d-inline-flex align-items-center gap-1"
                   style="font-size: 0.72rem;">
                    <i class="bi bi-cash-coin text-warning"></i> EMI Collect
                </a>
            @endcan
        </div>
    </div>

    <!-- Main Navigation List (Smooth Scrolling) -->
    <div class="offcanvas-body p-0 bg-white" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
        <x-layouts.admin-sidebar-nav id-prefix="mob-" />
    </div>

    <!-- Drawer Footer (Account & Logout) -->
    <div class="offcanvas-footer p-3 bg-light border-top mt-auto">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <small class="text-muted d-block font-monospace" style="font-size: 0.65rem;">SESSION ACTIVE</small>
                <small class="text-secondary fw-semibold" style="font-size: 0.725rem;">{{ date('d M Y, h:i A') }}</small>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span style="font-size: 0.75rem;">Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
