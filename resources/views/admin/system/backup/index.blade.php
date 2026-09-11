@extends('layouts.admin')

@section('title', 'Database Backup & Recovery - ' . config('app.name'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-database-fill-down text-primary me-2"></i>Database Backup & Disaster Recovery
        </h4>
        <p class="text-muted small mb-0">Generate on-demand SQL database dumps, manage secure server storage, download backup files, and maintain disaster recovery snapshots.</p>
    </div>

    @can('backup.create')
        <form action="{{ route('admin.system.backup.store') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-database-add fs-6"></i>
                <span>Create Database Backup</span>
            </button>
        </form>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Security & Storage Notice Card -->
<div class="alert alert-light border rounded-3 p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-start gap-3">
        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
            <i class="bi bi-shield-lock-fill fs-5"></i>
        </div>
        <div>
            <h6 class="fw-bold text-dark mb-1">Secure Private Storage Specification</h6>
            <p class="text-muted small mb-0">
                All database backup <code>.sql</code> files are stored in private server storage (<code>storage/app/private/backups/</code>). Direct public HTTP requests to backup files are strictly prohibited to protect application data, credentials, and tenant privacy.
            </p>
        </div>
    </div>
</div>

<!-- Backups Table Card -->
<x-ui.card class="p-0 shadow-sm border-0 bg-white overflow-hidden">
    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold text-dark mb-0 font-heading">
            <i class="bi bi-list-columns-reverse text-primary me-2"></i>Existing Database Backups
        </h6>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-monospace">
            {{ count($backups) }} {{ Str::plural('Backup', count($backups)) }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table tg-data-table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" style="min-width: 240px;">BACKUP FILENAME</th>
                    <th scope="col" style="min-width: 180px;">CREATED AT</th>
                    <th scope="col" style="min-width: 110px;">FILE SIZE</th>
                    <th scope="col" style="min-width: 160px;">STORAGE MODE</th>
                    <th scope="col" class="text-end" style="min-width: 180px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $b)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                    <i class="bi bi-filetype-sql fs-6"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark font-monospace d-block small">{{ $b['filename'] }}</span>
                                    <small class="text-muted extra-small">SQL Database Dump Snapshot</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="d-block small text-dark fw-semibold">{{ $b['created_at']->format('M d, Y') }}</span>
                            <small class="text-muted font-monospace extra-small">{{ $b['created_at']->format('h:i:s A') }} ({{ $b['created_at']->diffForHumans() }})</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                {{ $b['size_formatted'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small">
                                <i class="bi bi-lock-fill me-1"></i>Private Server Storage
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                @can('backup.download')
                                    <a href="{{ route('admin.system.backup.download', $b['filename']) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1"
                                       title="Download SQL File">
                                        <i class="bi bi-download"></i>
                                        <span>Download</span>
                                    </a>
                                @endcan

                                @can('backup.delete')
                                    <form action="{{ route('admin.system.backup.destroy', $b['filename']) }}"
                                          method="POST"
                                          class="d-inline m-0"
                                          onsubmit="return confirm('Are you sure you want to permanently delete this database backup file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1"
                                                title="Delete Backup File">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="py-4">
                                <div class="bg-light rounded-circle p-3 d-inline-flex mb-3">
                                    <i class="bi bi-database-slash text-muted fs-1"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No Database Backups Found</h6>
                                <p class="text-muted small mb-3">Click the "Create Database Backup" button above to generate your first database snapshot.</p>
                                @can('backup.create')
                                    <form action="{{ route('admin.system.backup.store') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 py-2 fw-bold">
                                            <i class="bi bi-plus-lg me-1"></i> Create First Backup Now
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
