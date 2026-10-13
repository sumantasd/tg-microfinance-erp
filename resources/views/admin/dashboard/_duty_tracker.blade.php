<!-- EMPLOYEE DUTY & REAL-TIME ATTENDANCE TRACKER -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden" id="employeeDutyTrackerCard">
    <div class="card-header border-0 bg-light-subtle px-3 px-sm-4 py-2.5 py-sm-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-clock-history fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0 font-heading">Employee Duty & Live Attendance</h6>
                <small class="text-muted" style="font-size: 0.75rem;">Verified server-side timestamps & real-time GPS tracking</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span id="dutyStatusBadge" class="badge {{ $dutyInfo['badge_class'] ?? 'bg-secondary text-white' }} rounded-pill px-3 py-2 font-monospace shadow-xs" style="font-size: 0.75rem;">
                @if(($dutyInfo['duty_status'] ?? '') === 'active')
                    <span class="spinner-grow spinner-grow-sm me-1 text-white" role="status" style="width: 0.5rem; height: 0.5rem;"></span>
                @endif
                <span id="dutyStatusText">{{ $dutyInfo['status_label'] ?? 'Off Duty' }}</span>
            </span>
            <span id="gpsLiveIndicator" class="badge bg-light text-secondary border rounded-pill px-2.5 py-1.5" style="font-size: 0.72rem;">
                <i class="bi bi-geo-alt me-1 text-primary"></i><span id="gpsLiveText">GPS Ready</span>
            </span>
        </div>
    </div>

    @if(!empty($dutyInfo['employee']))
        <div class="bg-primary-subtle border-bottom border-primary-subtle px-3 px-sm-4 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2" id="linkedEmployeeBanner">
            <div class="d-flex align-items-center gap-2 small flex-wrap">
                <i class="bi bi-person-badge-fill text-primary fs-6"></i>
                <span class="fw-bold text-dark">{{ $dutyInfo['employee']->full_name }}</span>
                <span class="badge bg-primary text-white font-monospace rounded-pill">{{ $dutyInfo['employee']->employee_code }}</span>
                @if($dutyInfo['employee']->designation)
                    <span class="text-secondary fw-semibold">| {{ $dutyInfo['employee']->designation->title }}</span>
                @endif
                @if($dutyInfo['employee']->branch)
                    <span class="text-muted">| {{ $dutyInfo['employee']->branch->name }}</span>
                @endif
            </div>
            <span class="badge bg-white text-success border border-success-subtle rounded-pill font-monospace py-1 px-2.5" style="font-size: 0.72rem;">
                <i class="bi bi-check-circle-fill me-1 text-success"></i>Profile Linked
            </span>
        </div>
    @elseif(!($dutyInfo['has_profile'] ?? false))
        <div class="alert alert-warning border-0 rounded-0 mb-0 px-3 px-sm-4 py-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2" id="unlinkedEmployeeAlert">
            <div class="d-flex align-items-center gap-2 small text-dark">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                <div>
                    <strong>No Employee Profile Linked:</strong>
                    <span>This user account is not currently linked to an active employee record. Duty check-in and GPS tracking require a linked employee profile.</span>
                </div>
            </div>
            @can('users.edit')
                <a href="{{ route('admin.system.users.edit', auth()->id()) }}" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold shadow-xs">
                    <i class="bi bi-link-45deg me-1"></i> Link Profile
                </a>
            @endcan
        </div>
    @endif

    <div class="card-body p-3 p-sm-4">
        <!-- METRICS ROW (2-Column on Mobile, 4-Column on Desktop) -->
        <div class="row g-2 g-sm-3 mb-3 mb-sm-4">
            <!-- CHECK-IN TIME -->
            <div class="col-6 col-md-3">
                <div class="p-2.5 p-sm-3 bg-light rounded-3 text-center border h-100">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">CHECK-IN TIME</small>
                    <div class="fs-5 fs-sm-4 fw-bold text-dark font-monospace mt-1" id="metricClockIn">
                        {{ $dutyInfo['clock_in_formatted'] ?? '--:--' }}
                    </div>
                    <small class="text-muted opacity-75" style="font-size: 0.68rem;" id="subClockIn">Server Verified</small>
                </div>
            </div>

            <!-- CHECK-OUT TIME -->
            <div class="col-6 col-md-3">
                <div class="p-2.5 p-sm-3 bg-light rounded-3 text-center border h-100">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">CHECK-OUT TIME</small>
                    <div class="fs-5 fs-sm-4 fw-bold text-dark font-monospace mt-1" id="metricClockOut">
                        {{ $dutyInfo['clock_out_formatted'] ?? '--:--' }}
                    </div>
                    <small class="text-muted opacity-75" style="font-size: 0.68rem;" id="subClockOut">Server Verified</small>
                </div>
            </div>

            <!-- WORKING DURATION / ELAPSED TIMER -->
            <div class="col-6 col-md-3">
                <div class="p-2.5 p-sm-3 bg-primary-subtle text-primary rounded-3 text-center border border-primary-subtle h-100">
                    <small class="text-primary fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">WORKING DURATION</small>
                    <div class="fs-6 fs-sm-4 fw-bold font-monospace mt-1 text-truncate" id="metricDuration">
                        @if(($dutyInfo['duty_status'] ?? '') === 'completed')
                            {{ $dutyInfo['working_duration'] ?? '--:--' }}
                        @elseif(($dutyInfo['duty_status'] ?? '') === 'active')
                            00h 00m 00s
                        @else
                            --:--
                        @endif
                    </div>
                    <small class="text-primary opacity-75" style="font-size: 0.68rem;" id="subDuration">
                        {{ ($dutyInfo['duty_status'] ?? '') === 'active' ? 'Live Elapsed' : (($dutyInfo['duty_status'] ?? '') === 'completed' ? 'Shift Total' : 'Not Started') }}
                    </small>
                </div>
            </div>

            <!-- ACCURACY / LOCATION STATUS -->
            <div class="col-6 col-md-3">
                <div class="p-2.5 p-sm-3 bg-light rounded-3 text-center border h-100">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">LAST GPS PING</small>
                    <div class="fs-5 fs-sm-5 fw-bold text-dark font-monospace mt-1 text-truncate" id="metricGpsTime">
                        @if(!empty($dutyInfo['last_ping_time_formatted']))
                            {{ $dutyInfo['last_ping_time_formatted'] }}
                        @elseif(!empty($dutyInfo['last_ping']))
                            {{ \Carbon\Carbon::parse($dutyInfo['last_ping']->recorded_at, 'UTC')->setTimezone(config('app.business_timezone', 'Asia/Kolkata'))->format('h:i A') }}
                        @else
                            --:--
                        @endif
                    </div>
                    <small class="text-muted opacity-75" style="font-size: 0.68rem;" id="subGpsAccuracy">
                        @if(!empty($dutyInfo['last_ping']) && $dutyInfo['last_ping']->accuracy)
                            Acc: ±{{ round($dutyInfo['last_ping']->accuracy, 1) }}m
                        @else
                            Awaiting Session
                        @endif
                    </small>
                </div>
            </div>
        </div>

        <!-- ACTION ROW -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pt-2.5 border-top">
            <div class="small text-muted" id="dutyStatusDescription">
                @if(!($dutyInfo['has_profile'] ?? false))
                    <span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i> No Employee Profile: An administrator must link or generate an employee profile for this user account.</span>
                @elseif(($dutyInfo['duty_status'] ?? '') === 'active')
                    <i class="bi bi-info-circle-fill text-success me-1"></i> You are currently on active duty. Periodic GPS coordinates are syncing in the background.
                @elseif(($dutyInfo['duty_status'] ?? '') === 'completed')
                    <i class="bi bi-check-circle-fill text-primary me-1"></i> Duty shift for today has ended. Total duration: <strong>{{ $dutyInfo['working_duration'] }}</strong>.
                @else
                    <i class="bi bi-info-circle me-1"></i> Press <strong>Check-In & Start Duty</strong> to begin your working hours and start live field tracking.
                @endif
            </div>

            <div class="d-flex align-items-center gap-2 w-100 w-sm-auto justify-content-sm-end" id="dutyActionButtonsContainer">
                <!-- Check-in Button (Shown when not started, prominent full-width on mobile) -->
                <button type="button"
                        id="btnDutyCheckIn"
                        class="btn btn-success btn-lg rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm w-100 w-sm-auto {{ ($dutyInfo['duty_status'] ?? '') !== 'not_started' ? 'd-none' : '' }}"
                        style="min-height: 48px; font-size: 0.925rem;"
                        {{ !($dutyInfo['has_profile'] ?? false) ? 'disabled title="Employee profile mapping required"' : '' }}>
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Check-In & Start Duty</span>
                </button>

                <!-- Check-out Button (Shown when active, prominent full-width on mobile) -->
                <button type="button"
                        id="btnDutyCheckOut"
                        class="btn btn-danger btn-lg rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm w-100 w-sm-auto {{ ($dutyInfo['duty_status'] ?? '') !== 'active' ? 'd-none' : '' }}"
                        style="min-height: 48px; font-size: 0.925rem;"
                        data-bs-toggle="modal"
                        data-bs-target="#dutyCheckOutModal">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>End Duty & Check-Out</span>
                </button>

                <!-- Completed Badge (Shown when completed) -->
                <div id="badgeDutyCompleted" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2.5 fw-bold w-100 w-sm-auto text-center {{ ($dutyInfo['duty_status'] ?? '') !== 'completed' ? 'd-none' : '' }}">
                    <i class="bi bi-lock-fill me-1"></i> Shift Closed for Today
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHECK-OUT CONFIRMATION POPUP MODAL -->
<div class="modal fade" id="dutyCheckOutModal" tabindex="-1" aria-labelledby="dutyCheckOutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                <h6 class="modal-title fw-bold font-heading d-flex align-items-center gap-2" id="dutyCheckOutModalLabel">
                    <i class="bi bi-exclamation-octagon-fill"></i> Confirm Duty Shift Check-Out
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 58px; height: 58px;">
                        <i class="bi bi-door-closed fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">End working shift for today?</h5>
                    <p class="text-muted small mb-0">Concluding your shift will capture your final GPS location, log server clock-out time, and calculate total working hours.</p>
                </div>

                <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Shift Started At:</span>
                        <strong class="text-dark font-monospace" id="modalShiftStartTime">{{ $dutyInfo['clock_in_formatted'] ?? '--:--' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Elapsed Duty Time:</span>
                        <strong class="text-primary font-monospace" id="modalShiftElapsedTime">Calculating...</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Location Verification:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" id="modalGpsStatus">
                            <i class="bi bi-geo-alt me-1"></i> GPS Ready
                        </span>
                    </div>
                </div>

                <div class="alert alert-warning border border-warning-subtle py-2.5 px-3 small rounded-3 mb-0" role="alert">
                    <i class="bi bi-shield-exclamation me-1"></i> Once ended, check-in cannot be re-opened today to ensure payroll and audit compliance.
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3 px-4 d-flex flex-column-reverse flex-sm-row justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold w-100 w-sm-auto" data-bs-dismiss="modal">
                    Cancel & Stay On Duty
                </button>
                <button type="button" id="btnConfirmCheckOutModal" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5 w-100 w-sm-auto">
                    <i class="bi bi-check2-circle"></i>
                    <span id="btnConfirmCheckOutText">Yes, End Duty Now</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Current Duty State Hydration
    let dutyState = {
        status: "{{ $dutyInfo['duty_status'] ?? 'not_started' }}", // 'not_started', 'active', 'completed'
        clockIn: "{{ $dutyInfo['clock_in'] ?? '' }}",
        clockInFormatted: "{{ $dutyInfo['clock_in_formatted'] ?? '' }}",
        clockOut: "{{ $dutyInfo['clock_out'] ?? '' }}",
        clockOutFormatted: "{{ $dutyInfo['clock_out_formatted'] ?? '' }}",
        elapsedSeconds: parseInt("{{ $dutyInfo['elapsed_seconds'] ?? 0 }}", 10),
        workingDuration: "{{ $dutyInfo['working_duration'] ?? '' }}",
        lastPingTime: "{{ $dutyInfo['last_ping_time_formatted'] ?? '' }}"
    };

    let timerInterval = null;
    let gpsPingInterval = null;
    const GPS_PING_INTERVAL_MS = 60000; // 60 seconds periodic ping

    document.addEventListener('DOMContentLoaded', function() {
        initDutyState();
    });

    function initDutyState() {
        if (dutyState.status === 'active') {
            startLiveElapsedTimer();
            startPeriodicGpsTracking();
        } else if (dutyState.status === 'completed') {
            stopLiveElapsedTimer();
            stopPeriodicGpsTracking();
        }

        setupDutyEventListeners();
    }

    function setupDutyEventListeners() {
        // 1. Check-In Button
        const btnCheckIn = document.getElementById('btnDutyCheckIn');
        if (btnCheckIn) {
            btnCheckIn.addEventListener('click', handleCheckIn);
        }

        // 2. Open Modal Update
        const btnCheckOut = document.getElementById('btnDutyCheckOut');
        if (btnCheckOut) {
            btnCheckOut.addEventListener('click', function() {
                const elapsedEl = document.getElementById('modalShiftElapsedTime');
                const startEl = document.getElementById('modalShiftStartTime');
                if (elapsedEl) elapsedEl.textContent = formatDuration(dutyState.elapsedSeconds);
                if (startEl) startEl.textContent = dutyState.clockInFormatted || '--:--';
            });
        }

        // 3. Confirm Check-Out Modal Button
        const btnConfirmCheckOut = document.getElementById('btnConfirmCheckOutModal');
        if (btnConfirmCheckOut) {
            btnConfirmCheckOut.addEventListener('click', handleCheckOut);
        }
    }

    /**
     * Handle Check-In with GPS verification.
     */
    function handleCheckIn() {
        const btn = document.getElementById('btnDutyCheckIn');
        if (!btn || btn.disabled) return;

        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser. Please use an updated modern browser.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Acquiring GPS...`;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Recording Check-In...`;

                fetch("{{ route('admin.duty.check-in') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng,
                        accuracy: accuracy,
                        remarks: 'Web Employee Dashboard Check-In'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Check-In & Start Duty`;

                    if (data.success) {
                        // Successfully Checked In
                        dutyState.status = 'active';
                        dutyState.clockIn = data.data.clock_in;
                        dutyState.clockInFormatted = data.data.clock_in_formatted;
                        dutyState.elapsedSeconds = 0;

                        updateUiToActive(data.data);
                        startLiveElapsedTimer();
                        startPeriodicGpsTracking();

                        // Visual Toast / Alert
                        showDutyToast('Duty Shift Started', data.message || 'Have a productive shift!', 'success');
                    } else {
                        alert(data.message || 'Check-in failed. Please try again.');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Check-In & Start Duty`;
                    console.error('Check-in network error:', err);
                    alert('Network error while processing check-in. Please check your connectivity.');
                });
            },
            function(error) {
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Check-In & Start Duty`;
                handleGeoError(error);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    /**
     * Handle Check-Out with GPS verification.
     */
    function handleCheckOut() {
        const btn = document.getElementById('btnConfirmCheckOutModal');
        const btnText = document.getElementById('btnConfirmCheckOutText');
        if (!btn || btn.disabled) return;

        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        btn.disabled = true;
        if (btnText) btnText.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Acquiring GPS...`;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                if (btnText) btnText.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Ending Shift...`;

                fetch("{{ route('admin.duty.check-out') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng,
                        accuracy: accuracy,
                        remarks: 'Web Employee Dashboard Check-Out'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    if (btnText) btnText.textContent = 'Yes, End Duty Now';

                    // Dismiss modal
                    const modalEl = document.getElementById('dutyCheckOutModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    if (data.success) {
                        dutyState.status = 'completed';
                        dutyState.clockOut = data.data.clock_out;
                        dutyState.clockOutFormatted = data.data.clock_out_formatted;
                        dutyState.workingDuration = data.data.working_duration;

                        stopLiveElapsedTimer();
                        stopPeriodicGpsTracking();
                        updateUiToCompleted(data.data);

                        showDutyToast('Duty Shift Concluded', data.message || `Total shift duration: ${data.data.working_duration}`, 'primary');
                    } else {
                        alert(data.message || 'Check-out failed. Please try again.');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    if (btnText) btnText.textContent = 'Yes, End Duty Now';
                    console.error('Check-out network error:', err);
                    alert('Network error while processing check-out. Please check your connectivity.');
                });
            },
            function(error) {
                btn.disabled = false;
                if (btnText) btnText.textContent = 'Yes, End Duty Now';
                handleGeoError(error);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    /**
     * Send periodic background GPS ping.
     */
    function sendPeriodicPing() {
        if (dutyState.status !== 'active') return;
        if (!navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                fetch("{{ route('admin.duty.ping') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        speed: pos.coords.speed || null,
                        notes: 'Periodic live GPS dashboard ping'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.is_active) {
                        const timeStr = data.recorded_at_formatted || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        document.getElementById('metricGpsTime').textContent = timeStr;
                        document.getElementById('subGpsAccuracy').textContent = `Acc: ±${Math.round(pos.coords.accuracy)}m`;
                        document.getElementById('gpsLiveText').textContent = 'Tracking Active';
                    } else if (data.is_active === false) {
                        stopPeriodicGpsTracking();
                    }
                })
                .catch(err => console.warn('Periodic GPS ping notice:', err));
            },
            function(err) {
                console.warn('Periodic GPS acquisition notice:', err.message);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    }

    function startLiveElapsedTimer() {
        stopLiveElapsedTimer();
        updateDurationDisplay();

        timerInterval = setInterval(function() {
            dutyState.elapsedSeconds++;
            updateDurationDisplay();
        }, 1000);
    }

    function stopLiveElapsedTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function startPeriodicGpsTracking() {
        stopPeriodicGpsTracking();
        sendPeriodicPing(); // initial ping
        gpsPingInterval = setInterval(sendPeriodicPing, GPS_PING_INTERVAL_MS);
    }

    function stopPeriodicGpsTracking() {
        if (gpsPingInterval) {
            clearInterval(gpsPingInterval);
            gpsPingInterval = null;
        }
    }

    function updateDurationDisplay() {
        const el = document.getElementById('metricDuration');
        if (el && dutyState.status === 'active') {
            el.textContent = formatDuration(dutyState.elapsedSeconds);
        }
    }

    function formatDuration(totalSeconds) {
        if (isNaN(totalSeconds) || totalSeconds < 0) totalSeconds = 0;
        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;
        return `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
    }

    function updateUiToActive(data) {
        // Status Badge
        const badge = document.getElementById('dutyStatusBadge');
        badge.className = 'badge bg-success text-white rounded-pill px-3 py-2 font-monospace shadow-xs';
        badge.innerHTML = `<span class="spinner-grow spinner-grow-sm me-1 text-white" role="status" style="width: 0.5rem; height: 0.5rem;"></span><span id="dutyStatusText">Active On Duty</span>`;

        // Live GPS Badge
        document.getElementById('gpsLiveText').textContent = 'Live Tracking Active';

        // Metric Values
        document.getElementById('metricClockIn').textContent = data.clock_in_formatted;
        document.getElementById('subClockIn').textContent = 'Started Today';
        document.getElementById('metricClockOut').textContent = '--:--';
        document.getElementById('subClockOut').textContent = 'In Progress';
        document.getElementById('subDuration').textContent = 'Live Elapsed';

        if (data.gps_location) {
            document.getElementById('metricGpsTime').textContent = data.clock_in_formatted;
            document.getElementById('subGpsAccuracy').textContent = data.gps_location.accuracy ? `Acc: ±${Math.round(data.gps_location.accuracy)}m` : 'GPS Acquired';
        }

        // Buttons Toggle
        document.getElementById('btnDutyCheckIn').classList.add('d-none');
        document.getElementById('btnDutyCheckOut').classList.remove('d-none');
        document.getElementById('badgeDutyCompleted').classList.add('d-none');

        // Description
        document.getElementById('dutyStatusDescription').innerHTML = `<i class="bi bi-info-circle-fill text-success me-1"></i> You are currently on active duty. Periodic GPS coordinates are syncing in the background.`;
    }

    function updateUiToCompleted(data) {
        // Status Badge
        const badge = document.getElementById('dutyStatusBadge');
        badge.className = 'badge bg-primary text-white rounded-pill px-3 py-2 font-monospace shadow-xs';
        badge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i><span id="dutyStatusText">Duty Completed</span>`;

        // Live GPS Badge
        document.getElementById('gpsLiveText').textContent = 'Shift Closed';

        // Metric Values
        document.getElementById('metricClockOut').textContent = data.clock_out_formatted;
        document.getElementById('subClockOut').textContent = 'Shift Ended';
        document.getElementById('metricDuration').textContent = data.working_duration;
        document.getElementById('subDuration').textContent = 'Shift Total';

        // Buttons Toggle
        document.getElementById('btnDutyCheckIn').classList.add('d-none');
        document.getElementById('btnDutyCheckOut').classList.add('d-none');
        document.getElementById('badgeDutyCompleted').classList.remove('d-none');

        // Description
        document.getElementById('dutyStatusDescription').innerHTML = `<i class="bi bi-check-circle-fill text-primary me-1"></i> Duty shift for today has ended. Total duration: <strong>${data.working_duration}</strong>.`;
    }

    function handleGeoError(error) {
        let msg = 'Unable to retrieve your location.';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                msg = 'GPS Location permission denied. Please allow location access in your browser to verify attendance.';
                break;
            case error.POSITION_UNAVAILABLE:
                msg = 'Location information is currently unavailable. Please ensure your device GPS is turned on.';
                break;
            case error.TIMEOUT:
                msg = 'Location request timed out. Please try again in an area with clear GPS reception.';
                break;
        }
        alert(msg);
    }

    function showDutyToast(title, body, type = 'success') {
        // Simple elegant notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} shadow-lg rounded-4 position-fixed bottom-0 end-0 m-4 p-3 border-0 d-flex align-items-center gap-2`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <i class="bi bi-check-circle-fill fs-4 text-${type}"></i>
            <div>
                <strong class="d-block">${title}</strong>
                <small class="text-dark">${body}</small>
            </div>
            <button type="button" class="btn-close ms-3" onclick="this.parentElement.remove()"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 6000);
    }
})();
</script>
@endpush
