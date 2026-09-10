@extends('layouts.admin')

@section('title', 'Expense Categories - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-tags text-primary me-2"></i>Expense Categories
        </h4>
        <p class="text-muted small mb-0">Manage chart-of-accounts mapping and operational cost categories.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('expense.category.manage')
        <button type="button" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
        @endcan
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Expenses
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.expenses.categories.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by category name or code..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.expenses.categories.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Category Name</th>
                        <th>Parent Category</th>
                        <th>GL Account Mapping</th>
                        <th>Usage Count</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">{{ $category->category_code ?? 'N/A' }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $category->category_name }}</div>
                            @if($category->description)
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">{{ $category->description }}</small>
                            @endif
                        </td>
                        <td>
                            @if($category->parent)
                                <span class="badge bg-light text-dark border">{{ $category->parent->category_name }}</span>
                            @else
                                <span class="text-muted small">None (Top Level)</span>
                            @endif
                        </td>
                        <td>
                            @if($category->chartOfAccount)
                                <span class="badge bg-soft-info text-info border border-info px-2 py-1">
                                    <i class="bi bi-book me-1"></i>{{ $category->chartOfAccount->account_code }} - {{ $category->chartOfAccount->account_name }}
                                </span>
                            @else
                                <span class="text-muted small">Default (5300 Operating Expense)</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">{{ $category->expenses_count ?? $category->expenses->count() }} records</span>
                        </td>
                        <td>
                            @if($category->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @can('expense.category.manage')
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('admin.expenses.categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('admin.expenses.categories.update', $category->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-bottom-0 pb-0">
                                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Expense Category</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                                            <input type="text" name="category_name" class="form-control" value="{{ $category->category_name }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Category Code</label>
                                                <input type="text" name="category_code" class="form-control text-uppercase" value="{{ $category->category_code }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Parent Category</label>
                                                <select name="parent_id" class="form-select">
                                                    <option value="">None (Top Level)</option>
                                                    @foreach($parentCategories as $pCat)
                                                        @if($pCat->id != $category->id)
                                                            <option value="{{ $pCat->id }}" {{ $category->parent_id == $pCat->id ? 'selected' : '' }}>{{ $pCat->category_name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Chart of Account Mapping</label>
                                            <select name="chart_of_account_id" class="form-select">
                                                <option value="">Default Operating Expense Account (5300)</option>
                                                @foreach($chartOfAccounts as $coa)
                                                    <option value="{{ $coa->id }}" {{ $category->chart_of_account_id == $coa->id ? 'selected' : '' }}>{{ $coa->account_code }} - {{ $coa->account_name }} ({{ strtoupper($coa->account_type) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $category->description }}</textarea>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_active_{{ $category->id }}" value="1" {{ $category->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="edit_active_{{ $category->id }}">Active Category</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-tags display-5 d-block text-muted opacity-50 mb-2"></i>
                            No expense categories found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())
            <div class="p-3 border-top">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.expenses.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>New Expense Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Office Stationery, Fuel & Transport" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Category Code</label>
                            <input type="text" name="category_code" class="form-control text-uppercase" placeholder="e.g. CAT-STAT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Parent Category</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (Top Level)</option>
                                @foreach($parentCategories as $pCat)
                                    <option value="{{ $pCat->id }}">{{ $pCat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chart of Account Mapping</label>
                        <select name="chart_of_account_id" class="form-select">
                            <option value="">Default Operating Expense Account (5300)</option>
                            @foreach($chartOfAccounts as $coa)
                                <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }} ({{ strtoupper($coa->account_type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional category description..."></textarea>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="create_is_active" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="create_is_active">Active Category</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
