@extends('layouts.admin')

@section('title', 'Field Staff Tracking & Route History - Grihalaxmi Finance')

@push('styles')
<!-- Leaflet CSS for Map Rendering -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #routeMap {
        height: 480px;
        width: 100%;
        border-radius: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Field Staff Location Tracking & Route History</h4>
        <p class="text-muted small mb-0">Monitor authorized staff duty pings, real-time status, daily verified distance, and GPS route history.</p>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary font-monospace px-3 py-2 border border-primary-subtle rounded-pill">
            <i class="bi bi-calendar-event me-1"></i>Date: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
        </span>
    </div>
</div>

<!-- Filter Strip -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.field-tracking.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Date</label>
            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Branch</label>
            <select name="branch_id" class="form-select form-select-sm">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ (int)($filters['branch_id'] ?? 0) === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Staff Member</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Staff</option>
                @foreach($fieldStaffUsers as $u)
                    <option value="{{ $u->id }}" {{ (int)($filters['user_id'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
            <a href="{{ route('admin.field-tracking.index') }}" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- 1. STAFF DUTY & REAL-TIME STATUS TABLE -->
<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white mb-4">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Staff Real-Time Status & Verified Distance ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})</h6>
        <span class="badge bg-secondary-subtle text-secondary border font-monospace small">Total Staff: {{ $staffTrackingSummary->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Staff Name</th>
                    <th>Branch</th>
                    <th>Duty Status</th>
                    <th>Last Location Update</th>
                    <th>Battery</th>
                    <th class="text-end">Verified Distance</th>
                    <th class="text-end pe-3">Route Evidence</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffTrackingSummary as $item)
                    @php
                        $u = $item['user'];
                        $last = $item['last_ping'];
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark">{{ $u->name }}</div>
                            <small class="text-muted">{{ $u->roles->first()?->name ?? 'Field Officer' }}</small>
                        </td>
                        <td>{{ $u->branch->name ?? 'Head Office' }}</td>
                        <td>
                            <span class="badge {{ $item['badge_class'] }} rounded-pill px-2.5 py-1 text-uppercase">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ $item['status_label'] }}
                            </span>
                        </td>
                        <td>
                            @if($last)
                                <div class="font-monospace fw-semibold text-dark">{{ $last->recorded_at->format('h:i A') }}</div>
                                <small class="text-muted" title="{{ $last->location_address }}">{{ $last->recorded_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted small">No pings recorded</span>
                            @endif
                        </td>
                        <td>
                            @if(isset($item['battery_level']) && $item['battery_level'] !== null)
                                <span class="badge bg-light text-dark border font-monospace">
                                    <i class="bi bi-battery-charging text-success me-1"></i>{{ $item['battery_level'] }}%
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end font-monospace fw-bold text-primary fs-6">
                            {{ number_format($item['verified_distance_km'], 2) }} km
                        </td>
                        <td class="text-end pe-3">
                            @if($item['ping_count'] > 0)
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-view-route" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}" data-date="{{ $selectedDate }}">
                                    <i class="bi bi-map me-1"></i> View Route
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 disabled" disabled>No Pings</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No staff members found for the selected scope.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<!-- 2. RECORDED FIELD VISITS & TRIP LOGS -->
<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white mb-4">
    <div class="p-3 border-bottom bg-light">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Recorded Field Visits & Trip Logs</h6>
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

<!-- ROUTE HISTORY MAP MODAL -->
<div class="modal fade" id="routeMapModal" tabindex="-1" aria-labelledby="routeMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white py-3 border-bottom">
                <div>
                    <h6 class="modal-title fw-bold text-dark font-heading" id="routeMapModalLabel"><i class="bi bi-map text-primary me-2"></i>Daily GPS Route History</h6>
                    <small id="modalStaffSubTitle" class="text-muted font-monospace"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-sm-4">
                        <div class="bg-white p-2 border rounded-3 text-center">
                            <small class="text-muted d-block" style="font-size: 0.68rem;">VERIFIED DISTANCE</small>
                            <strong id="modalVerifiedKm" class="text-primary font-monospace fs-5">0.00 km</strong>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="bg-white p-2 border rounded-3 text-center">
                            <small class="text-muted d-block" style="font-size: 0.68rem;">GPS BREADCRUMBS</small>
                            <strong id="modalPointCount" class="text-dark font-monospace fs-5">0</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="bg-white p-2 border rounded-3 text-center">
                            <small class="text-muted d-block" style="font-size: 0.68rem;">DATE RECORDED</small>
                            <strong id="modalDateLabel" class="text-secondary font-monospace fs-6">—</strong>
                        </div>
                    </div>
                </div>
                <div id="routeMap"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Leaflet JS for Interactive Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let map = null;
    let polyline = null;
    let markersGroup = L.layerGroup();

    const modalEl = document.getElementById('routeMapModal');
    const modal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.btn-view-route').forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const date = this.dataset.date;

            document.getElementById('modalStaffSubTitle').textContent = `Staff: ${userName} | Date: ${date}`;
            document.getElementById('modalDateLabel').textContent = date;
            document.getElementById('modalVerifiedKm').textContent = 'Loading...';
            document.getElementById('modalPointCount').textContent = '...';

            modal.show();

            fetch(`{{ url('/admin/field-tracking/route-history') }}/${userId}?date=${date}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modalVerifiedKm').textContent = `${data.verified_distance_km.toFixed(2)} km`;
                    document.getElementById('modalPointCount').textContent = data.total_points;

                    setTimeout(() => {
                        initMap(data.points);
                    }, 300);
                })
                .catch(err => {
                    alert('Error loading route history data');
                });
        });
    });

    function initMap(points) {
        if (!map) {
            map = L.map('routeMap').setView([22.5726, 88.3639], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            markersGroup.addTo(map);
        } else {
            markersGroup.clearLayers();
            if (polyline) map.removeLayer(polyline);
            map.invalidateSize();
        }

        if (points.length === 0) return;

        const latLngs = points.map(p => [p.lat, p.lng]);
        polyline = L.polyline(latLngs, { color: '#2563eb', weight: 4, opacity: 0.8 }).addTo(map);

        points.forEach((p, idx) => {
            const isStart = idx === 0;
            const isEnd = idx === points.length - 1;
            const color = isStart ? 'green' : (isEnd ? 'red' : 'blue');

            const marker = L.circleMarker([p.lat, p.lng], {
                radius: isStart || isEnd ? 8 : 5,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            });

            marker.bindPopup(`
                <div style="font-size: 12px;">
                    <strong>${p.event_type.replace('_', ' ').toUpperCase()}</strong><br>
                    Time: ${p.time}<br>
                    GPS: ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)}<br>
                    ${p.battery ? 'Battery: ' + p.battery + '%<br>' : ''}
                    ${p.address ? 'Address: ' + p.address : ''}
                </div>
            `);

            markersGroup.addLayer(marker);
        });

        map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
    }
});
</script>
@endpush
