@extends('layouts.admin')

@section('title', 'Media Library - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-folder-symlink me-2 text-primary"></i>Media Library</h4>
        <p class="text-muted small mb-0">Browse, upload, and manage secure enterprise documents and digital assets.</p>
    </div>
    @can('media.upload')
    <div>
        <button type="button" class="btn btn-primary rounded-pill px-3.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
            <i class="bi bi-cloud-arrow-up-fill me-1.5"></i> Upload New File
        </button>
    </div>
    @endcan
</div>

<!-- Filter Strip -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="GET" action="{{ route('admin.media.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-secondary mb-1">Search File Name / Title</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="Search files...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">File Type</label>
            <select name="file_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="image" {{ ($filters['file_type'] ?? '') === 'image' ? 'selected' : '' }}>Images</option>
                <option value="pdf" {{ ($filters['file_type'] ?? '') === 'pdf' ? 'selected' : '' }}>PDF Documents</option>
                <option value="document" {{ ($filters['file_type'] ?? '') === 'document' ? 'selected' : '' }}>Word / Text Documents</option>
                <option value="spreadsheet" {{ ($filters['file_type'] ?? '') === 'spreadsheet' ? 'selected' : '' }}>Spreadsheets / Excel</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Collection</label>
            <input type="text" name="collection" value="{{ $filters['collection'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. general, kyc, cms">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
            <a href="{{ route('admin.media.index') }}" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Media Grid -->
<div class="row g-3 mb-4">
    @forelse($mediaFiles as $file)
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden position-relative">
                <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center border-bottom">
                    @if($file->isImage())
                        <img src="{{ asset('storage/' . $file->file_path) }}" alt="{{ $file->title }}" class="object-fit-cover w-100 h-100">
                    @elseif($file->file_type === 'pdf')
                        <div class="d-flex flex-column align-items-center justify-content-center text-danger p-3">
                            <i class="bi bi-file-earmark-pdf fs-1"></i>
                            <span class="small fw-bold mt-1">PDF</span>
                        </div>
                    @elseif($file->file_type === 'spreadsheet')
                        <div class="d-flex flex-column align-items-center justify-content-center text-success p-3">
                            <i class="bi bi-file-earmark-excel fs-1"></i>
                            <span class="small fw-bold mt-1">EXCEL</span>
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center text-primary p-3">
                            <i class="bi bi-file-earmark-text fs-1"></i>
                            <span class="small fw-bold mt-1">DOC</span>
                        </div>
                    @endif
                </div>
                <div class="p-2.5 d-flex flex-column justify-content-between h-100">
                    <div>
                        <div class="fw-bold text-dark text-truncate small" title="{{ $file->title }}">{{ $file->title }}</div>
                        <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $file->formatted_size }} • {{ $file->created_at->format('M d, Y') }}</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                        <a href="{{ route('admin.media.download', $file->id) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 small" title="Download">
                            <i class="bi bi-download"></i> Download
                        </a>
                        @can('media.delete')
                        <form method="POST" action="{{ route('admin.media.destroy', $file->id) }}" onsubmit="return confirm('Are you sure you want to delete this media file?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger border-0 p-0" title="Delete File"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 py-5 text-center text-muted">
            <i class="bi bi-folder2-open fs-1 text-muted d-block mb-2"></i>
            <div class="fw-bold">No media files found</div>
            <small>Upload images or documents to populate the media library.</small>
        </div>
    @endforelse
</div>

@if($mediaFiles->hasPages())
    <div class="d-flex justify-content-center mb-4">
        {{ $mediaFiles->links() }}
    </div>
@endif

<!-- Upload Modal -->
<div class="modal fade" id="uploadMediaModal" tabindex="-1" aria-labelledby="uploadMediaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="uploadMediaModalLabel"><i class="bi bi-cloud-upload me-2"></i>Upload File to Media Library</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Select File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control form-control-sm" required>
                        <div class="form-text small">Max size: 10MB. Allowed types: Images, PDF, Word, Excel, CSV.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Title / Caption</label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Branch Photo, KYC Terms PDF">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Collection Tag</label>
                        <input type="text" name="collection" class="form-control form-control-sm" value="general" placeholder="e.g. general, kyc, cms">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-upload me-1"></i> Upload Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
