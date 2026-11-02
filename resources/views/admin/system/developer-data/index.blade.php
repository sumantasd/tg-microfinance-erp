@extends('layouts.admin')

@section('title', 'Data Management - ' . config('app.name'))

@section('content')
<div class="container-fluid px-0">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-database-gear text-primary me-2"></i>Data Management
            </h4>
            <p class="text-muted small mb-0">
                Manage full SQL database snapshots, safely restore verified backups, and selectively clean operational data categories while preserving core master configuration.
            </p>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4 border-0" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-check-circle-fill fs-5 mt-0.5 text-success"></i>
                <div class="flex-grow-1">
                    <span class="fw-bold">Success:</span> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4 border-0" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 mt-0.5 text-danger"></i>
                <div class="flex-grow-1">
                    <span class="fw-bold">Error:</span> {{ session('error') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm mb-4 border-0" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill fs-5 mt-0.5 text-info"></i>
                <div class="flex-grow-1">{{ session('info') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- SECTION 1: DATABASE BACKUP -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 font-heading">
                    <i class="bi bi-database-fill-down text-primary me-2"></i>Database Backup
                </h5>
                <p class="text-muted small mb-0">Create and download complete SQL database backups stored in protected private storage.</p>
            </div>
            <div>
                <form action="{{ route('admin.system.developer-data.backup') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-database-add fs-6"></i>
                        <span>Create Database Backup</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted text-uppercase font-monospace" style="font-size: 0.75rem;">
                    <tr>
                        <th class="ps-4">Backup Filename</th>
                        <th>Created Date & Time</th>
                        <th>File Size</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($backups as $b)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-code text-primary fs-5"></i>
                                    <div>
                                        <div class="font-monospace fw-bold text-dark small">{{ $b['filename'] }}</div>
                                        <span class="text-muted" style="font-size: 0.7rem;">Private Storage</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small text-dark fw-semibold">{{ $b['created_at']->format('d M Y, h:i A') }}</span>
                                <span class="d-block text-muted" style="font-size: 0.7rem;">{{ $b['created_at']->diffForHumans() }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark font-monospace border px-2.5 py-1">
                                    {{ $b['size_formatted'] }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($b['is_verified']))
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 font-monospace small">
                                        <i class="bi bi-check-circle-fill me-1"></i>Verified Complete
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 font-monospace small">
                                        Available
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <a href="{{ route('admin.system.developer-data.backup.download', ['filename' => $b['filename']]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-download"></i>
                                        <span>Download</span>
                                    </a>

                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1 btn-delete-backup" data-filename="{{ $b['filename'] }}">
                                        <i class="bi bi-trash3"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-database-slash fs-2 d-block text-secondary mb-2"></i>
                                <span class="fw-semibold">No database backups available.</span>
                                <p class="small text-muted mb-0">Click "Create Database Backup" to generate a snapshot.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 2: DATABASE RESTORE -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 font-heading">
                    <i class="bi bi-arrow-counterclockwise text-warning me-2"></i>Database Restore
                </h5>
                <p class="text-muted small mb-0">
                    Restore the database from an existing verified SQL snapshot. An automatic backup of the current database will be taken before restoring.
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-warning rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="btn-open-restore-modal" {{ count($backups) === 0 ? 'disabled' : '' }}>
                    <i class="bi bi-arrow-counterclockwise fs-6"></i>
                    <span>Restore Database</span>
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            @if(count($backups) > 0)
                <div class="row align-items-center g-3">
                    <div class="col-lg-7">
                        <label class="form-label fw-bold text-dark small mb-1" for="restore-backup-select">
                            Select Stored Backup to Restore:
                        </label>
                        <select class="form-select font-monospace" id="restore-backup-select">
                            @foreach($backups as $idx => $b)
                                <option value="{{ $b['filename'] }}" data-size="{{ $b['size_formatted'] }}" data-date="{{ $b['created_at']->format('d M Y, h:i A') }}" {{ $idx === 0 ? 'selected' : '' }}>
                                    {{ $b['filename'] }} ({{ $b['size_formatted'] }} &bull; {{ $b['created_at']->format('d M Y, h:i A') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Only verified backups stored in private server storage can be selected.</div>
                    </div>

                    <div class="col-lg-5">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.72rem;">Selected Backup Details</div>
                            <div class="font-monospace fw-bold text-dark small text-truncate" id="sel-backup-filename">{{ $backups[0]['filename'] ?? '' }}</div>
                            <div class="small text-muted mt-1 d-flex justify-content-between">
                                <span>Created: <strong id="sel-backup-date">{{ $backups[0]['created_at']->format('d M Y, h:i A') ?? '' }}</strong></span>
                                <span>Size: <strong id="sel-backup-size">{{ $backups[0]['size_formatted'] ?? '' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-database-slash fs-2 d-block text-secondary mb-2"></i>
                    <span class="fw-semibold">No backups available for restoration.</span>
                    <p class="small text-muted mb-0">Generate a backup in the Database Backup section above before restoring.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- SECTION 3: SELECTIVE DATA DELETE -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 font-heading">
                    <i class="bi bi-trash3 text-danger me-2"></i>Selective Data Delete
                </h5>
                <p class="text-muted small mb-0">
                    Select categories to permanently delete transactional records. System settings, loan schemes, company branches, and administrator accounts are preserved.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3" id="btn-select-all">
                    Select All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3" id="btn-deselect-all">
                    Deselect All
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3.5 shadow-sm" id="btn-preview-data">
                    <i class="bi bi-eye me-1"></i>Preview Selected Data
                </button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill fw-bold px-3.5 shadow-sm" id="btn-delete-selected">
                    <i class="bi bi-trash3-fill me-1"></i>Delete Selected Data
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <form id="cleanup-selection-form">
                <div class="row g-3">
                    @foreach($modules as $key => $mod)
                        @php
                            $affectedCount = $overview['transactional_data'][$key]['count'] ?? 0;
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <label class="card h-100 border rounded-3 p-3 cursor-pointer module-card shadow-sm-hover position-relative" style="cursor: pointer; transition: all 0.2s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input module-checkbox" type="checkbox" name="modules[]" value="{{ $key }}" id="mod-{{ $key }}" {{ $affectedCount > 0 ? 'checked' : '' }}>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                                            <span class="fw-bold text-dark">
                                                <i class="bi {{ $mod['icon'] }} text-primary me-1.5"></i>{{ $mod['name'] }}
                                            </span>
                                            <span class="badge {{ $affectedCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-muted border' }} font-monospace">
                                                {{ number_format($affectedCount) }}
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-0" style="font-size: 0.82rem; line-height: 1.4;">
                                            {{ $mod['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

</div>

<!-- PREVIEW MODAL -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-light border-bottom p-3">
                <h5 class="modal-title fw-bold text-dark" id="previewModalLabel">
                    <i class="bi bi-eye text-primary me-2"></i>Preview Selected Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="preview-modal-body">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted small">Calculating affected records from the database...</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" id="btn-preview-proceed-to-delete">
                    Proceed to Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- RESTORE DATABASE CONFIRMATION MODAL (PASSWORD PROTECTED) -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark border-bottom-0 p-3.5">
                <h5 class="modal-title fw-bold" id="restoreModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirm Database Restoration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 small">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i>Warning: Database Replacement</div>
                    Restoring from a backup will replace current database information with the selected snapshot. Any transactions recorded after the backup was taken will be overwritten.
                </div>

                <div class="alert alert-info border-0 rounded-3 p-3 mb-3 small">
                    <div class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i>Pre-Restore Safety Backup:</div>
                    A full verified backup of the current database will be created automatically before restoration starts. If the safety backup fails, restoration stops immediately.
                </div>

                <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="small text-muted fw-semibold">Target Backup Snapshot:</div>
                    <div class="font-monospace fw-bold text-dark small text-truncate" id="modal-restore-filename">database_backup.sql</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ack-restore-replace">
                    <label class="form-check-label fw-bold text-dark small" for="ack-restore-replace">
                        I understand that this operation will replace the current database with the selected backup snapshot.
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="restore-password-input">
                        Enter Data Management Password to Confirm:
                    </label>
                    <input type="password" id="restore-password-input" class="form-control" placeholder="Enter your Data Management Password" required autocomplete="off">
                    <div class="form-text small text-muted">Server-side password verification is required.</div>
                </div>

                <div id="restore-progress-alert" class="alert alert-warning border-0 rounded-3 p-3 d-none">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-border spinner-border-sm text-dark" role="status"></div>
                        <span class="small fw-semibold text-dark">Creating pre-restore safety backup and restoring database... Please do not close this window.</span>
                    </div>
                </div>

                <div id="restore-error-alert" class="alert alert-danger border-0 rounded-3 p-2.5 small d-none"></div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal" id="btn-cancel-restore">Cancel</button>
                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold" id="btn-confirm-restore" disabled>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Confirm & Restore Database
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE DATA CONFIRMATION MODAL (PASSWORD PROTECTED) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white border-bottom-0 p-3.5">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Data Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 rounded-3 p-3 mb-3 small">
                    <div class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i>Automatic Pre-Deletion Backup:</div>
                    A full database backup will be automatically created and verified before deletion begins. If backup generation fails, deletion will stop immediately.
                </div>

                <div id="live-records-warning" class="alert alert-warning border-0 rounded-3 p-3 mb-3 small d-none">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i>Active Records Detected:</div>
                    <div id="live-records-warning-text">The selected categories include active loan, customer, or financial records.</div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="ack-live-records">
                        <label class="form-check-label fw-bold text-dark" for="ack-live-records">
                            I understand this will permanently delete records in the selected categories.
                        </label>
                    </div>
                </div>

                <p class="text-dark small mb-3">
                    Selected operational data will be permanently removed. System configuration, settings, loan schemes, company branches, and administrator accounts will be preserved.
                </p>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ack-financial-reset">
                    <label class="form-check-label fw-bold text-dark small" for="ack-financial-reset">
                        I understand that clearing vouchers will reset all general ledger balances to ₹0.00 until an Opening Balance Journal Voucher is posted.
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="deletion-confirmation-phrase">
                        Type Confirmation Phrase to Confirm:
                    </label>
                    <input type="text" id="deletion-confirmation-phrase" class="form-control font-monospace" placeholder="Type CLEAN DEMO DATA" required autocomplete="off">
                    <div class="form-text small text-muted">You must type <code class="fw-bold text-danger">CLEAN DEMO DATA</code> exactly to enable deletion.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="deletion-password-input">
                        Enter Data Management Password to Confirm:
                    </label>
                    <input type="password" id="deletion-password-input" class="form-control" placeholder="Enter your Data Management Password" required autocomplete="off">
                    <div class="form-text small text-muted">Server-side password verification is required.</div>
                </div>

                <div id="delete-progress-alert" class="alert alert-primary border-0 rounded-3 p-3 d-none">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="small fw-semibold">Creating verified database backup and deleting records... Please wait.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal" id="btn-cancel-delete">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" id="btn-confirm-delete" disabled>
                    <i class="bi bi-trash3-fill me-1"></i>Confirm & Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE BACKUP CONFIRMATION MODAL (PASSWORD PROTECTED) -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-labelledby="deleteBackupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white border-bottom-0 p-3.5">
                <h5 class="modal-title fw-bold" id="deleteBackupModalLabel">
                    <i class="bi bi-trash3-fill me-2"></i>Confirm Delete Backup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark small mb-2">
                    Are you sure you want to delete the backup file:
                </p>
                <div class="p-2.5 bg-light rounded-3 border font-monospace fw-bold text-dark small mb-3" id="del-backup-filename-display">
                    database_backup.sql
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="backup-delete-password-input">
                        Enter Data Management Password to Confirm:
                    </label>
                    <input type="password" id="backup-delete-password-input" class="form-control" placeholder="Enter your Data Management Password" required autocomplete="off">
                </div>
                <div id="del-backup-error" class="alert alert-danger border-0 rounded-3 p-2.5 small d-none"></div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" id="btn-confirm-delete-backup" disabled>
                    Delete Backup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
    const deleteBackupModal = new bootstrap.Modal(document.getElementById('deleteBackupModal'));

    // Select/Deselect All Checkbox controls
    const selectAllBtn = document.getElementById('btn-select-all');
    const deselectAllBtn = document.getElementById('btn-deselect-all');
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');

    if (selectAllBtn && deselectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            moduleCheckboxes.forEach(cb => cb.checked = true);
        });
        deselectAllBtn.addEventListener('click', function () {
            moduleCheckboxes.forEach(cb => cb.checked = false);
        });
    }

    function getSelectedModules() {
        const selected = [];
        document.querySelectorAll('.module-checkbox:checked').forEach(cb => selected.push(cb.value));
        return selected;
    }

    let currentHasLiveRecords = false;

    // Restore Backup Select Change Listener
    const restoreBackupSelect = document.getElementById('restore-backup-select');
    const selBackupFilename = document.getElementById('sel-backup-filename');
    const selBackupDate = document.getElementById('sel-backup-date');
    const selBackupSize = document.getElementById('sel-backup-size');

    if (restoreBackupSelect) {
        restoreBackupSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt) {
                if (selBackupFilename) selBackupFilename.textContent = opt.value;
                if (selBackupDate) selBackupDate.textContent = opt.getAttribute('data-date');
                if (selBackupSize) selBackupSize.textContent = opt.getAttribute('data-size');
            }
        });
    }

    // Open Restore Modal
    const openRestoreBtn = document.getElementById('btn-open-restore-modal');
    const modalRestoreFilename = document.getElementById('modal-restore-filename');
    const ackRestoreReplace = document.getElementById('ack-restore-replace');
    const restorePasswordInput = document.getElementById('restore-password-input');
    const confirmRestoreBtn = document.getElementById('btn-confirm-restore');
    const restoreProgressAlert = document.getElementById('restore-progress-alert');
    const restoreErrorAlert = document.getElementById('restore-error-alert');
    const cancelRestoreBtn = document.getElementById('btn-cancel-restore');

    function checkRestoreButtonState() {
        const hasPassword = restorePasswordInput.value.trim().length > 0;
        const acknowledged = ackRestoreReplace.checked;
        confirmRestoreBtn.disabled = !(hasPassword && acknowledged);
    }

    if (openRestoreBtn) {
        openRestoreBtn.addEventListener('click', function () {
            const selectedFilename = restoreBackupSelect ? restoreBackupSelect.value : '';
            if (!selectedFilename) {
                alert('Please select a backup file to restore.');
                return;
            }

            modalRestoreFilename.textContent = selectedFilename;
            restorePasswordInput.value = '';
            ackRestoreReplace.checked = false;
            restoreErrorAlert.classList.add('d-none');
            checkRestoreButtonState();
            restoreModal.show();
        });
    }

    if (ackRestoreReplace) {
        ackRestoreReplace.addEventListener('change', checkRestoreButtonState);
    }

    if (restorePasswordInput) {
        restorePasswordInput.addEventListener('input', checkRestoreButtonState);
    }

    if (confirmRestoreBtn) {
        confirmRestoreBtn.addEventListener('click', function () {
            const filename = modalRestoreFilename.textContent.trim();
            const password = restorePasswordInput.value;

            confirmRestoreBtn.disabled = true;
            cancelRestoreBtn.disabled = true;
            restorePasswordInput.disabled = true;
            ackRestoreReplace.disabled = true;
            restoreProgressAlert.classList.remove('d-none');
            restoreErrorAlert.classList.add('d-none');

            fetch("{{ route('admin.system.developer-data.restore') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    filename: filename,
                    password: password,
                    confirm_replace: 1
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 400) {
                    throw new Error(body.error || 'Database restoration failed.');
                }
                alert(body.message || 'Database restored successfully!');
                window.location.reload();
            })
            .catch(err => {
                restoreErrorAlert.textContent = err.message;
                restoreErrorAlert.classList.remove('d-none');
                restoreProgressAlert.classList.add('d-none');
                restorePasswordInput.disabled = false;
                ackRestoreReplace.disabled = false;
                cancelRestoreBtn.disabled = false;
                checkRestoreButtonState();
            });
        });
    }

    // Preview Selected Data Button
    const previewBtn = document.getElementById('btn-preview-data');
    const previewBody = document.getElementById('preview-modal-body');

    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            const selected = getSelectedModules();
            if (selected.length === 0) {
                alert('Please select at least one category to preview.');
                return;
            }

            previewBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted small">Inspecting database dependencies and calculating candidate records...</div>
                </div>
            `;
            previewModal.show();

            fetch("{{ route('admin.system.developer-data.preview') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ modules: selected })
            })
            .then(res => res.json())
            .then(data => {
                currentHasLiveRecords = !!data.has_live_records;

                let html = `
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-danger-subtle text-danger rounded-3 border border-danger-subtle">
                                <div class="small fw-semibold text-uppercase font-monospace">Candidate Scope for Deletion</div>
                                <div class="fs-4 fw-bold font-monospace mt-1">${(data.total_records_to_delete || 0).toLocaleString()} <span class="fs-6 fw-normal text-muted">records</span></div>
                                <div class="small text-muted mt-0.5">${data.tables_in_deletion_order ? data.tables_in_deletion_order.length : 0} affected database tables</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-success-subtle text-success rounded-3 border border-success-subtle">
                                <div class="small fw-semibold text-uppercase font-monospace">Strictly Preserved Master Data</div>
                                <div class="fs-4 fw-bold font-monospace mt-1">${(data.total_preserved_records || 0).toLocaleString()} <span class="fs-6 fw-normal text-muted">records</span></div>
                                <div class="small text-muted mt-0.5">${data.total_preserved_tables || 56} core configuration tables</div>
                            </div>
                        </div>
                    </div>
                `;

                if (data.financial_warnings && data.financial_warnings.length > 0) {
                    html += `
                        <div class="alert alert-warning border-0 rounded-3 p-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1.5">
                                <i class="bi bi-calculator-fill fs-5 text-warning"></i>
                                <h6 class="fw-bold text-dark mb-0 font-heading">Financial & Ledger Balance Warning:</h6>
                            </div>
                            <ul class="mb-0 small ps-3">
                    `;
                    data.financial_warnings.forEach(w => {
                        html += `<li class="mb-1">${w}</li>`;
                    });
                    html += `</ul></div>`;
                }

                if (data.has_live_records && data.live_warnings) {
                    html += `
                        <div class="alert alert-danger-subtle border-0 rounded-3 p-3 mb-3">
                            <h6 class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Active Records Warning:</h6>
                            <ul class="mb-0 small ps-3">
                    `;
                    data.live_warnings.forEach(w => {
                        html += `<li>${w}</li>`;
                    });
                    html += `</ul></div>`;
                }

                if (data.missing_dependencies && Object.keys(data.missing_dependencies).length > 0) {
                    html += `
                        <div class="alert alert-danger border-0 rounded-3 p-3 mb-3">
                            <h6 class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-octagon me-1"></i>Dependency Alert:</h6>
                            <ul class="mb-0 small ps-3">
                    `;
                    for (const [mod, deps] of Object.entries(data.missing_dependencies)) {
                        const depList = Array.isArray(deps) ? deps.join(', ') : deps;
                        html += `<li>Category <strong>${mod}</strong> requires <strong>${depList}</strong> to be selected together to prevent foreign key errors.</li>`;
                    }
                    html += `</ul></div>`;
                }

                html += `
                    <ul class="nav nav-pills nav-fill mb-3 p-1 bg-light rounded-3" id="previewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold small py-1.5" id="deletion-tab" data-bs-toggle="pill" data-bs-target="#tab-deletion" type="button" role="tab">
                                <i class="bi bi-trash3 text-danger me-1"></i>Proposed Deletions (${data.tables_in_deletion_order ? data.tables_in_deletion_order.length : 0})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold small py-1.5" id="preserved-tab" data-bs-toggle="pill" data-bs-target="#tab-preserved" type="button" role="tab">
                                <i class="bi bi-shield-check text-success me-1"></i>Preserved Master (${data.preserved_tables ? data.preserved_tables.length : 56})
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="previewTabsContent">
                        <div class="tab-pane fade show active" id="tab-deletion" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">Ordered Leaf-to-Root Execution Sequence:</span>
                                <span class="badge bg-light text-dark font-monospace small border">Foreign Keys Handled</span>
                            </div>
                            <div class="table-responsive border rounded-3 mb-0" style="max-height: 280px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0 font-monospace small">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th class="ps-3" style="width: 50px;">Seq</th>
                                            <th>Table Name</th>
                                            <th class="text-end pe-3">Affected Records</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                if (data.tables_in_deletion_order && data.tables_in_deletion_order.length > 0) {
                    data.tables_in_deletion_order.forEach((item, idx) => {
                        const tableName = item.table || item;
                        const recordCount = typeof item.count !== 'undefined' ? item.count.toLocaleString() : 'Selected';
                        html += `
                            <tr>
                                <td class="ps-3 text-muted">${idx + 1}</td>
                                <td class="fw-bold text-dark">${tableName}</td>
                                <td class="text-end pe-3 text-danger fw-semibold">${recordCount}</td>
                            </tr>
                        `;
                    });
                } else {
                    html += `<tr><td colspan="3" class="text-center py-3 text-muted">No tables selected.</td></tr>`;
                }

                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-preserved" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">Protected Master Tables (Never Deleted):</span>
                                <span class="badge bg-success-subtle text-success font-monospace small">100% Retained</span>
                            </div>
                            <div class="table-responsive border rounded-3 mb-0" style="max-height: 280px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0 font-monospace small">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th class="ps-3" style="width: 50px;">#</th>
                                            <th>Protected Master Table</th>
                                            <th class="text-end pe-3">Current Records</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                if (data.preserved_tables && data.preserved_tables.length > 0) {
                    data.preserved_tables.forEach((item, idx) => {
                        html += `
                            <tr>
                                <td class="ps-3 text-muted">${idx + 1}</td>
                                <td class="fw-semibold text-dark">${item.table}</td>
                                <td class="text-end pe-3 text-success fw-bold">${item.count.toLocaleString()}</td>
                            </tr>
                        `;
                    });
                }

                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;

                previewBody.innerHTML = html;
            })
            .catch(err => {
                previewBody.innerHTML = `
                    <div class="alert alert-danger border-0 rounded-3 p-3">
                        Failed to calculate preview: ${err.message}
                    </div>
                `;
            });
        });
    }

    // Modal transition from Preview to Delete Confirmation
    const proceedToDeleteBtn = document.getElementById('btn-preview-proceed-to-delete');
    if (proceedToDeleteBtn) {
        proceedToDeleteBtn.addEventListener('click', function () {
            previewModal.hide();
            setTimeout(() => openDeleteModal(), 300);
        });
    }

    // Direct Delete Selected Data Button
    const deleteSelectedBtn = document.getElementById('btn-delete-selected');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function () {
            const selected = getSelectedModules();
            if (selected.length === 0) {
                alert('Please select at least one category to delete.');
                return;
            }
            openDeleteModal();
        });
    }

    const deletionPasswordInput = document.getElementById('deletion-password-input');
    const deletionConfirmationPhrase = document.getElementById('deletion-confirmation-phrase');
    const ackFinancialReset = document.getElementById('ack-financial-reset');
    const confirmDeleteBtn = document.getElementById('btn-confirm-delete');
    const deleteProgressAlert = document.getElementById('delete-progress-alert');
    const cancelDeleteBtn = document.getElementById('btn-cancel-delete');
    const liveRecordsWarning = document.getElementById('live-records-warning');
    const ackLiveRecords = document.getElementById('ack-live-records');

    function checkDeleteButtonState() {
        const hasPassword = deletionPasswordInput && deletionPasswordInput.value.trim().length > 0;
        const phraseOk = deletionConfirmationPhrase && deletionConfirmationPhrase.value.trim() === 'CLEAN DEMO DATA';
        const financialAck = ackFinancialReset && ackFinancialReset.checked;
        let acknowledged = true;

        if (currentHasLiveRecords) {
            acknowledged = ackLiveRecords && ackLiveRecords.checked;
        }

        confirmDeleteBtn.disabled = !(hasPassword && phraseOk && financialAck && acknowledged);
    }

    function openDeleteModal() {
        if (deletionPasswordInput) deletionPasswordInput.value = '';
        if (deletionConfirmationPhrase) deletionConfirmationPhrase.value = '';
        if (ackFinancialReset) ackFinancialReset.checked = false;
        if (ackLiveRecords) ackLiveRecords.checked = false;

        if (currentHasLiveRecords) {
            liveRecordsWarning.classList.remove('d-none');
        } else {
            liveRecordsWarning.classList.add('d-none');
        }

        checkDeleteButtonState();
        deleteModal.show();
    }

    if (deletionPasswordInput) {
        deletionPasswordInput.addEventListener('input', checkDeleteButtonState);
    }

    if (deletionConfirmationPhrase) {
        deletionConfirmationPhrase.addEventListener('input', checkDeleteButtonState);
    }

    if (ackFinancialReset) {
        ackFinancialReset.addEventListener('change', checkDeleteButtonState);
    }

    if (ackLiveRecords) {
        ackLiveRecords.addEventListener('change', checkDeleteButtonState);
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            const selected = getSelectedModules();
            const password = deletionPasswordInput ? deletionPasswordInput.value : '';
            const phrase = deletionConfirmationPhrase ? deletionConfirmationPhrase.value.trim() : '';

            confirmDeleteBtn.disabled = true;
            cancelDeleteBtn.disabled = true;
            if (deletionPasswordInput) deletionPasswordInput.disabled = true;
            if (deletionConfirmationPhrase) deletionConfirmationPhrase.disabled = true;
            if (ackFinancialReset) ackFinancialReset.disabled = true;
            if (ackLiveRecords) ackLiveRecords.disabled = true;
            deleteProgressAlert.classList.remove('d-none');

            fetch("{{ route('admin.system.developer-data.execute') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    modules: selected,
                    password: password,
                    confirmation_phrase: phrase
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 400) {
                    throw new Error(body.error || 'Data deletion failed.');
                }
                alert(body.message || 'Data deletion completed successfully!');
                window.location.reload();
            })
            .catch(err => {
                alert('Deletion Error: ' + err.message);
                deleteProgressAlert.classList.add('d-none');
                deletionPasswordInput.disabled = false;
                cancelDeleteBtn.disabled = false;
                confirmDeleteBtn.disabled = false;
            });
        });
    }

    // Delete Backup File Modal Logic
    let targetBackupFilename = '';
    const backupPasswordInput = document.getElementById('backup-delete-password-input');
    const confirmDeleteBackupBtn = document.getElementById('btn-confirm-delete-backup');
    const delBackupFilenameDisplay = document.getElementById('del-backup-filename-display');
    const delBackupError = document.getElementById('del-backup-error');

    document.querySelectorAll('.btn-delete-backup').forEach(btn => {
        btn.addEventListener('click', function () {
            targetBackupFilename = this.getAttribute('data-filename');
            delBackupFilenameDisplay.textContent = targetBackupFilename;
            backupPasswordInput.value = '';
            delBackupError.classList.add('d-none');
            confirmDeleteBackupBtn.disabled = true;
            deleteBackupModal.show();
        });
    });

    if (backupPasswordInput) {
        backupPasswordInput.addEventListener('input', function () {
            confirmDeleteBackupBtn.disabled = this.value.trim().length === 0;
        });
    }

    if (confirmDeleteBackupBtn) {
        confirmDeleteBackupBtn.addEventListener('click', function () {
            const password = backupPasswordInput.value;
            confirmDeleteBackupBtn.disabled = true;

            fetch(`/admin/system/developer-data/backup/delete/${encodeURIComponent(targetBackupFilename)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ password: password })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 400) {
                    throw new Error(body.error || 'Failed to delete backup.');
                }
                alert(body.message || 'Backup deleted successfully.');
                window.location.reload();
            })
            .catch(err => {
                delBackupError.textContent = err.message;
                delBackupError.classList.remove('d-none');
                confirmDeleteBackupBtn.disabled = false;
            });
        });
    }
});
</script>
@endpush
@endsection
