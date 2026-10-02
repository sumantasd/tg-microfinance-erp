@extends('layouts.admin')

@section('title', 'Live Staff Location Tracking Dashboard - Grihalaxmi Finance')

@push('styles')
<style>
    .map-dashboard-container {
        min-height: 600px;
    }
    #googleLiveMap {
        height: 600px;
        min-height: 600px;
        width: 100%;
        border-radius: 0.75rem;
        background-color: #f8fafc;
    }
    .staff-list-scroll {
        max-height: 540px;
        overflow-y: auto;
    }
    .staff-card-item {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid transparent;
    }
    .staff-card-item:hover {
        background-color: #f8fafc;
        transform: translateX(2px);
    }
    .staff-card-item.active {
        background-color: #eff6ff !important;
        border-left-color: #2563eb !important;
    }
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-dot-online { background-color: #16a34a; box-shadow: 0 0 6px #16a34a; }
    .status-dot-stale { background-color: #d97706; box-shadow: 0 0 6px #d97706; }
    .status-dot-offline { background-color: #dc2626; }
    
    .map-floating-controls {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 10;
    }
    .map-route-banner {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 10;
        max-width: 380px;
    }
    .summary-card {
        border-radius: 0.75rem;
        transition: transform 0.15s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1">
            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>Live Staff Location Tracking Dashboard
        </h4>
        <p class="text-muted small mb-0">Map-first real-time staff GPS location, duty status, and daily route history.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="form-check form-switch bg-white border px-3 py-1.5 rounded-pill shadow-sm mb-0">
            <input class="form-check-input mt-0" type="checkbox" id="autoRefreshToggle" checked>
            <label class="form-check-label small fw-bold text-secondary cursor-pointer" for="autoRefreshToggle">
                <i class="bi bi-arrow-repeat me-1 text-primary"></i>Auto Refresh (30s)
            </label>
        </div>
        <span class="badge bg-primary-subtle text-primary font-monospace px-3 py-2 border border-primary-subtle rounded-pill">
            <i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
        </span>
    </div>
</div>

<!-- 1. TOP SUMMARY CARDS BAR -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">TOTAL STAFF</small>
            <span class="fs-4 fw-bold text-dark font-monospace" id="metricTotalStaff">{{ $summaryMetrics['total_staff'] }}</span>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-success fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                <i class="bi bi-circle-fill me-1"></i>ONLINE
            </small>
            <span class="fs-4 fw-bold text-success font-monospace" id="metricOnline">{{ $summaryMetrics['online_count'] }}</span>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-warning fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>STALE
            </small>
            <span class="fs-4 fw-bold text-warning font-monospace" id="metricStale">{{ $summaryMetrics['stale_count'] }}</span>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-danger fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                <i class="bi bi-dash-circle-fill me-1"></i>OFFLINE
            </small>
            <span class="fs-4 fw-bold text-danger font-monospace" id="metricOffline">{{ $summaryMetrics['offline_count'] }}</span>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-primary fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                <i class="bi bi-geo-alt me-1"></i>TRACKED TODAY
            </small>
            <span class="fs-4 fw-bold text-primary font-monospace" id="metricTracked">{{ $summaryMetrics['tracked_count'] }}</span>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm p-3 bg-white summary-card text-center">
            <small class="text-info fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                <i class="bi bi-speedometer2 me-1"></i>TOTAL KM
            </small>
            <span class="fs-4 fw-bold text-info font-monospace" id="metricTotalKm">{{ number_format($summaryMetrics['total_km'], 2) }}</span>
        </div>
    </div>
</div>

<!-- 2. FILTER STRIP -->
<x-ui.card class="p-3 shadow-sm border-0 mb-3 bg-white">
    <form id="filterForm" method="GET" action="{{ route('admin.field-tracking.index') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Date</label>
            <input type="date" id="filterDate" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Branch</label>
            <select id="filterBranch" name="branch_id" class="form-select form-select-sm">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ (int)($filters['branch_id'] ?? 0) === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Staff Member</label>
            <select id="filterUser" name="user_id" class="form-select form-select-sm">
                <option value="">All Staff</option>
                @foreach($fieldStaffUsers as $u)
                    <option value="{{ $u->id }}" {{ (int)($filters['user_id'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Status</label>
            <select id="filterStatus" name="status" class="form-select form-select-sm">
                <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                <option value="online" {{ ($filters['status'] ?? '') === 'online' ? 'selected' : '' }}>Online</option>
                <option value="stale" {{ ($filters['status'] ?? '') === 'stale' ? 'selected' : '' }}>Stale</option>
                <option value="offline" {{ ($filters['status'] ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold">
                <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>
            <a href="{{ route('admin.field-tracking.index') }}" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</x-ui.card>

<!-- 3. MAP-FIRST DASHBOARD MAIN CONTAINER -->
<div class="row g-3 mb-4">
    <!-- LEFT SIDE: COMPACT SEARCHABLE STAFF LIST -->
    <div class="col-lg-4 col-md-5">
        <div class="card border-0 shadow-sm h-100 bg-white overflow-hidden">
            <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-people me-1 text-primary"></i>Staff Real-Time Status (<span id="staffListCount">{{ count($staffTrackingSummary) }}</span>)
                </h6>
                <span class="badge bg-white text-secondary border font-monospace small" id="lastRefreshTime">Refreshed just now</span>
            </div>
            <div class="p-2 border-bottom bg-white">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="staffSearchInput" class="form-control border-start-0 bg-light" placeholder="Search staff by name or branch...">
                </div>
            </div>
            <div class="staff-list-scroll p-2" id="staffListContainer">
                @forelse($staffTrackingSummary as $item)
                    @php
                        $hasLoc = $item['has_location'];
                        $dotClass = match($item['status']) {
                            'online' => 'status-dot-online',
                            'stale' => 'status-dot-stale',
                            default => 'status-dot-offline',
                        };
                    @endphp
                    <div class="staff-card-item card p-2 mb-2 border rounded-3" 
                         data-user-id="{{ $item['user_id'] }}"
                         data-staff-name="{{ $item['staff_name'] }}"
                         data-branch="{{ $item['branch_name'] }}"
                         data-status="{{ $item['status'] }}"
                         data-has-location="{{ $hasLoc ? '1' : '0' }}"
                         data-lat="{{ $item['last_ping_formatted']['latitude'] ?? '' }}"
                         data-lng="{{ $item['last_ping_formatted']['longitude'] ?? '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot {{ $dotClass }}" title="Status: {{ $item['status_label'] }}"></span>
                                <div>
                                    <div class="fw-bold text-dark me-1" style="font-size: 0.88rem;">{{ $item['staff_name'] }}</div>
                                    <small class="text-muted d-block" style="font-size: 0.73rem;">
                                        <i class="bi bi-building me-1"></i>{{ $item['branch_name'] }} • <span class="fw-semibold">{{ $item['role'] }}</span>
                                    </small>
                                </div>
                            </div>
                            <span class="badge {{ $item['badge_class'] }} rounded-pill px-2 py-0.5 text-uppercase" style="font-size: 0.65rem;">
                                {{ $item['status'] }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top" style="font-size: 0.75rem;">
                            <span class="text-muted">
                                @if($hasLoc)
                                    <i class="bi bi-clock-history me-1"></i>{{ $item['last_ping_formatted']['diff_human'] }}
                                @else
                                    <span class="badge bg-light text-muted border">No location today</span>
                                @endif
                            </span>
                            <span class="font-monospace fw-bold text-primary">
                                <i class="bi bi-signpost-split me-1"></i>{{ number_format($item['verified_distance_km'], 2) }} km
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                        No staff members found for the selected scope.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: LARGE GOOGLE MAP -->
    <div class="col-lg-8 col-md-7">
        <div class="card border-0 shadow-sm bg-white overflow-hidden position-relative map-dashboard-container">
            <!-- FLOATING MAP CONTROLS OVERLAY -->
            <div class="map-floating-controls d-flex gap-2">
                <button type="button" id="btnFitAllStaff" class="btn btn-sm btn-white bg-white border shadow-sm rounded-pill fw-bold text-dark px-3">
                    <i class="bi bi-arrows-angle-expand me-1 text-primary"></i> FIT ALL STAFF
                </button>
                <button type="button" id="btnResetMap" class="btn btn-sm btn-white bg-white border shadow-sm rounded-pill text-secondary px-2.5" title="Reset View">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>

            <!-- FLOATING ROUTE INFORMATION BANNER -->
            <div id="mapRouteBanner" class="map-route-banner card border-0 shadow-lg p-2.5 bg-dark text-white rounded-3 d-none">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-primary rounded-pill font-monospace small"><i class="bi bi-signpost-2 me-1"></i>TODAY'S ROUTE</span>
                    <button type="button" id="btnCloseRouteBanner" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
                </div>
                <div class="fw-bold text-truncate" id="routeBannerStaffName">Staff Route History</div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-secondary font-monospace small">
                    <span>Distance: <strong id="routeBannerKm" class="text-warning">0.00 km</strong></span>
                    <span>Pings: <strong id="routeBannerPings" class="text-info">0</strong></span>
                </div>
            </div>

            <!-- GOOGLE MAP CONTAINER -->
            <div id="googleLiveMap"></div>
        </div>
    </div>
</div>

<!-- 4. SECONDARY SECTION: RECORDED FIELD VISITS & TRIP LOGS -->
<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white mb-4">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Recorded Field Visits & Trip Logs</h6>
        <span class="badge bg-secondary-subtle text-secondary border font-monospace small">Total Visits: {{ $fieldVisits->total() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Recorded Time</th>
                    <th>Staff Name</th>
                    <th>Branch</th>
                    <th>Visit Purpose</th>
                    <th>Customer</th>
                    <th>GPS Coordinates</th>
                    <th class="pe-3">Address / Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fieldVisits as $visit)
                    <tr>
                        <td class="ps-3 font-monospace text-muted small">
                            {{ $visit->recorded_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $visit->user->name }}</div>
                            <div class="text-muted small">{{ $visit->user->email }}</div>
                        </td>
                        <td>{{ $visit->branch->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2.5 py-1">
                                {{ str_replace('_', ' ', strtoupper($visit->visit_type)) }}
                            </span>
                        </td>
                        <td>
                            @if($visit->customer)
                                <div class="fw-semibold text-dark">{{ $visit->customer->full_name }}</div>
                                <div class="font-monospace text-muted small">#{{ $visit->customer->customer_code }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="font-monospace small text-primary">
                            @if($visit->latitude && $visit->longitude)
                                <a href="https://maps.google.com/?q={{ $visit->latitude }},{{ $visit->longitude }}" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>{{ number_format($visit->latitude, 4) }}, {{ number_format($visit->longitude, 4) }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="pe-3 small text-secondary">
                            {{ $visit->location_address ?: $visit->notes ?: 'No additional details' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No field visits logged for the selected criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($fieldVisits->hasPages())
        <div class="p-3 border-top">
            {{ $fieldVisits->links() }}
        </div>
    @endif
</x-ui.card>
@endsection

@push('scripts')
<!-- 1. Google Maps MarkerClusterer CDN -->
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>

<!-- 2. Dashboard Logic Script (Defines window.initLiveTrackingDashboard globally BEFORE Google Maps API fires callback) -->
<script>
let gMap = null;
let markerCluster = null;
let staffMarkers = {}; // userId -> google.maps.Marker
let activeInfoWindow = null;
let currentRoutePolyline = null;
let routeMarkers = [];
let autoRefreshTimer = null;
let staffDataCache = @json($staffTrackingSummary);

window.initLiveTrackingDashboard = function() {
    const mapElement = document.getElementById('googleLiveMap');
    if (!mapElement) return;

    // Default center: India / Kolkata region or average of staff markers
    const defaultCenter = { lat: 22.5726, lng: 88.3639 };
    
    gMap = new google.maps.Map(mapElement, {
        zoom: 12,
        center: defaultCenter,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        zoomControl: true,
        mapTypeControl: true,
        scaleControl: true,
        streetViewControl: false,
        rotateControl: false,
        fullscreenControl: true,
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    });

    // Initialize MarkerClusterer safely
    if (typeof markerClusterer !== 'undefined') {
        const ClustererClass = markerClusterer.MarkerClusterer || markerClusterer;
        if (typeof ClustererClass === 'function') {
            try {
                markerCluster = new ClustererClass({ map: gMap, markers: [] });
            } catch (e) {
                console.warn('MarkerClusterer initialization notice:', e);
            }
        }
    }

    // Render initial markers
    renderStaffMarkers(staffDataCache);

    // Setup Event Listeners
    setupEventListeners();

    // Start Auto Refresh Polling if enabled
    startAutoRefresh();
};

function renderStaffMarkers(staffList) {
    // Clear existing markers & clusterer
    if (markerCluster) {
        markerCluster.clearMarkers();
    }
    Object.keys(staffMarkers).forEach(id => {
        staffMarkers[id].setMap(null);
    });
    staffMarkers = {};

    const bounds = new google.maps.LatLngBounds();
    let hasValidCoords = false;

    const markersArray = [];

    staffList.forEach(staff => {
        if (!staff.has_location || !staff.last_ping_formatted) return;

        const lat = parseFloat(staff.last_ping_formatted.latitude);
        const lng = parseFloat(staff.last_ping_formatted.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        const position = { lat, lng };
        bounds.extend(position);
        hasValidCoords = true;

        // Visual pin styling according to status
        const pinColor = staff.status === 'online' ? '#16a34a' : (staff.status === 'stale' ? '#d97706' : '#dc2626');
        
        const svgIcon = {
            path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
            fillColor: pinColor,
            fillOpacity: 1,
            strokeWeight: 1.5,
            strokeColor: "#ffffff",
            scale: 1.8,
            anchor: new google.maps.Point(12, 22),
        };

        const marker = new google.maps.Marker({
            position: position,
            map: gMap,
            title: staff.staff_name,
            icon: svgIcon,
            animation: google.maps.Animation.DROP
        });

        const infoContent = `
            <div style="min-width: 220px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <strong style="font-size: 14px; color: #0f172a;">${staff.staff_name}</strong>
                    <span style="font-size: 10px; font-weight: bold; text-transform: uppercase; background: ${pinColor}; color: #fff; padding: 2px 6px; border-radius: 10px;">
                        ${staff.status}
                    </span>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">
                    <i class="bi bi-building"></i> ${staff.branch_name} &bull; ${staff.role}
                </div>
                <div style="font-size: 12px; color: #334155; margin-bottom: 4px;">
                    <strong>Last Update:</strong> ${staff.last_ping_formatted.time_formatted} (${staff.last_ping_formatted.diff_human})
                </div>
                ${staff.battery_level !== null ? `<div style="font-size: 11px; color: #475569; margin-bottom: 4px;"><strong>Battery:</strong> ${staff.battery_level}%</div>` : ''}
                <div style="font-size: 12px; color: #2563eb; font-weight: bold; margin-bottom: 8px;">
                    Verified Today: ${parseFloat(staff.verified_distance_km).toFixed(2)} km
                </div>
                <div style="display: flex; gap: 6px; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 8px;">
                    <button type="button" onclick="loadStaffRoute(${staff.user_id}, '${staff.staff_name}')" style="background: #2563eb; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: 600; width: 100%;">
                        <i class="bi bi-map"></i> View Today's Route
                    </button>
                </div>
            </div>
        `;

        const infoWindow = new google.maps.InfoWindow({
            content: infoContent
        });

        marker.addListener('click', () => {
            if (activeInfoWindow) activeInfoWindow.close();
            infoWindow.open(gMap, marker);
            activeInfoWindow = infoWindow;
            highlightStaffListItem(staff.user_id);
        });

        staffMarkers[staff.user_id] = marker;
        markersArray.push(marker);
    });

    if (markerCluster) {
        markerCluster.addMarkers(markersArray);
    }

    if (hasValidCoords && gMap) {
        gMap.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
    }
}

function setupEventListeners() {
    // Search Filter in Staff List
    const searchInput = document.getElementById('staffSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('.staff-card-item').forEach(item => {
                const text = (item.dataset.staffName + ' ' + item.dataset.branch).toLowerCase();
                item.style.display = text.includes(term) ? 'block' : 'none';
            });
        });
    }

    // Staff Item Click Handler
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.staff-card-item');
        if (item) {
            const userId = item.dataset.userId;
            const hasLoc = item.dataset.hasLocation === '1';
            const lat = parseFloat(item.dataset.lat);
            const lng = parseFloat(item.dataset.lng);
            const staffName = item.dataset.staffName;

            highlightStaffListItem(userId);

            if (hasLoc && !isNaN(lat) && !isNaN(lng) && gMap) {
                const pos = { lat, lng };
                gMap.panTo(pos);
                gMap.setZoom(16);

                if (staffMarkers[userId]) {
                    google.maps.event.trigger(staffMarkers[userId], 'click');
                }
            } else {
                alert(`${staffName} has not recorded any GPS pings today.`);
            }
        }
    });

    // Fit All Staff Button
    document.getElementById('btnFitAllStaff')?.addEventListener('click', fitAllStaffOnMap);
    
    // Reset Map Button
    document.getElementById('btnResetMap')?.addEventListener('click', function() {
        fitAllStaffOnMap();
        clearRouteFromMap();
    });

    // Close Route Banner
    document.getElementById('btnCloseRouteBanner')?.addEventListener('click', clearRouteFromMap);

    // Auto Refresh Checkbox Toggle
    document.getElementById('autoRefreshToggle')?.addEventListener('change', function() {
        if (this.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
}

function highlightStaffListItem(userId) {
    document.querySelectorAll('.staff-card-item').forEach(el => {
        if (el.dataset.userId == userId) {
            el.classList.add('active');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            el.classList.remove('active');
        }
    });
}

function fitAllStaffOnMap() {
    if (!gMap) return;
    const bounds = new google.maps.LatLngBounds();
    let count = 0;

    Object.values(staffMarkers).forEach(m => {
        bounds.extend(m.getPosition());
        count++;
    });

    if (count > 0) {
        gMap.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
    }
}

function loadStaffRoute(userId, staffName) {
    const filterDate = document.getElementById('filterDate')?.value || '{{ $selectedDate }}';
    
    fetch(`{{ url('/admin/field-tracking/route-history') }}/${userId}?date=${filterDate}`)
        .then(res => res.json())
        .then(data => {
            if (!data.points || data.points.length === 0) {
                alert(`No route pings found for ${staffName} on ${filterDate}`);
                return;
            }

            drawStaffRouteOnMap(data, staffName);
        })
        .catch(err => {
            console.error('Error fetching route history:', err);
            alert('Failed to load staff route history.');
        });
}

function drawStaffRouteOnMap(routeData, staffName) {
    clearRouteFromMap();

    const points = routeData.points;
    const latLngs = points.map(p => ({ lat: p.lat, lng: p.lng }));

    // Draw Polyline
    currentRoutePolyline = new google.maps.Polyline({
        path: latLngs,
        geodesic: true,
        strokeColor: '#2563eb',
        strokeOpacity: 0.85,
        strokeWeight: 5,
        map: gMap
    });

    const bounds = new google.maps.LatLngBounds();

    points.forEach((p, idx) => {
        const isStart = idx === 0;
        const isEnd = idx === points.length - 1;
        const pos = { lat: p.lat, lng: p.lng };
        bounds.extend(pos);

        if (isStart || isEnd) {
            const markerColor = isStart ? '#16a34a' : '#dc2626';
            const labelText = isStart ? 'S' : 'E';

            const routeMarker = new google.maps.Marker({
                position: pos,
                map: gMap,
                label: { text: labelText, color: '#ffffff', fontWeight: 'bold' },
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: markerColor,
                    fillOpacity: 1,
                    strokeWeight: 2,
                    strokeColor: '#ffffff'
                },
                title: isStart ? `Start (${p.time})` : `Latest (${p.time})`
            });
            routeMarkers.push(routeMarker);
        }
    });

    gMap.fitBounds(bounds, { top: 60, right: 60, bottom: 60, left: 60 });

    // Show Route Banner
    const banner = document.getElementById('mapRouteBanner');
    if (banner) {
        document.getElementById('routeBannerStaffName').textContent = `${staffName} (${routeData.date})`;
        document.getElementById('routeBannerKm').textContent = `${routeData.verified_distance_km.toFixed(2)} km`;
        document.getElementById('routeBannerPings').textContent = routeData.total_points;
        banner.classList.remove('d-none');
    }
}

function clearRouteFromMap() {
    if (currentRoutePolyline) {
        currentRoutePolyline.setMap(null);
        currentRoutePolyline = null;
    }
    routeMarkers.forEach(m => m.setMap(null));
    routeMarkers = [];

    const banner = document.getElementById('mapRouteBanner');
    if (banner) banner.classList.add('d-none');
}

function startAutoRefresh() {
    stopAutoRefresh();
    autoRefreshTimer = setInterval(fetchUpdatedData, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

function fetchUpdatedData() {
    const date = document.getElementById('filterDate')?.value || '';
    const branchId = document.getElementById('filterBranch')?.value || '';
    const userId = document.getElementById('filterUser')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';

    const params = new URLSearchParams({
        format: 'json',
        date: date,
        branch_id: branchId,
        user_id: userId,
        status: status
    });

    fetch(`{{ route('admin.field-tracking.index') }}?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update Summary Cards
                if (data.summary) {
                    document.getElementById('metricTotalStaff').textContent = data.summary.total_staff;
                    document.getElementById('metricOnline').textContent = data.summary.online_count;
                    document.getElementById('metricStale').textContent = data.summary.stale_count;
                    document.getElementById('metricOffline').textContent = data.summary.offline_count;
                    document.getElementById('metricTracked').textContent = data.summary.tracked_count;
                    document.getElementById('metricTotalKm').textContent = data.summary.total_km.toFixed(2);
                }

                // Update Staff List & Markers
                if (data.staff) {
                    staffDataCache = data.staff;
                    renderStaffMarkers(data.staff);
                    updateStaffListHtml(data.staff);
                }

                const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('lastRefreshTime').textContent = `Refreshed ${nowTime}`;
            }
        })
        .catch(err => {
            console.error('Auto refresh error:', err);
        });
}

function updateStaffListHtml(staffList) {
    const container = document.getElementById('staffListContainer');
    document.getElementById('staffListCount').textContent = staffList.length;

    if (!container) return;

    if (staffList.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                No staff members found for the selected scope.
            </div>
        `;
        return;
    }

    let html = '';
    staffList.forEach(item => {
        const hasLoc = item.has_location;
        const dotClass = item.status === 'online' ? 'status-dot-online' : (item.status === 'stale' ? 'status-dot-stale' : 'status-dot-offline');
        const lat = item.last_ping_formatted ? item.last_ping_formatted.latitude : '';
        const lng = item.last_ping_formatted ? item.last_ping_formatted.longitude : '';
        const km = parseFloat(item.verified_distance_km).toFixed(2);

        html += `
            <div class="staff-card-item card p-2 mb-2 border rounded-3" 
                 data-user-id="${item.user_id}"
                 data-staff-name="${item.staff_name}"
                 data-branch="${item.branch_name}"
                 data-status="${item.status}"
                 data-has-location="${hasLoc ? '1' : '0'}"
                 data-lat="${lat}"
                 data-lng="${lng}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-dot ${dotClass}" title="Status: ${item.status_label}"></span>
                        <div>
                            <div class="fw-bold text-dark me-1" style="font-size: 0.88rem;">${item.staff_name}</div>
                            <small class="text-muted d-block" style="font-size: 0.73rem;">
                                <i class="bi bi-building me-1"></i>${item.branch_name} &bull; <span class="fw-semibold">${item.role}</span>
                            </small>
                        </div>
                    </div>
                    <span class="badge ${item.badge_class} rounded-pill px-2 py-0.5 text-uppercase" style="font-size: 0.65rem;">
                        ${item.status}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top" style="font-size: 0.75rem;">
                    <span class="text-muted">
                        ${hasLoc ? `<i class="bi bi-clock-history me-1"></i>${item.last_ping_formatted.diff_human}` : `<span class="badge bg-light text-muted border">No location today</span>`}
                    </span>
                    <span class="font-monospace fw-bold text-primary">
                        <i class="bi bi-signpost-split me-1"></i>${km} km
                    </span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}
</script>

<!-- 3. Google Maps JavaScript API with Key (Loaded AFTER callback is globally attached) -->
@php
    $googleApiKey = config('services.google.maps_api_key');
@endphp
@if($googleApiKey)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleApiKey }}&libraries=places,geometry&callback=initLiveTrackingDashboard" async defer></script>
@else
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapEl = document.getElementById('googleLiveMap');
            if (mapEl) {
                mapEl.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center bg-light text-muted">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Google Maps API Key Not Configured</h6>
                        <p class="small text-muted mb-0">Please set <code>GOOGLE_MAPS_API_KEY</code> in your <code>.env</code> file to render the interactive live tracking dashboard map.</p>
                    </div>
                `;
            }
        });
    </script>
@endif
@endpush
