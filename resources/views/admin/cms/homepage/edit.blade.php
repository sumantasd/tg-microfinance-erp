@extends('layouts.admin')

@section('title', 'Edit Homepage Section - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Homepage Section</h4>
        <p class="text-muted small mb-0">Update section fields, image asset, mission/vision details, CTA, and display settings.</p>
    </div>
    <a href="{{ route('admin.cms.homepage.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Sections List
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix validation errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.cms.homepage.update', $section->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Section Unique Key *</label>
                <input type="text" name="section_key" value="{{ old('section_key', $section->section_key) }}" class="form-control bg-light font-monospace" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Section Title</label>
                <input type="text" name="title" value="{{ old('title', $section->title) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $section->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $section->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Description / Content Body</label>
                <textarea name="description" rows="4" class="form-control bg-light">{{ old('description', $section->description) }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button CTA Text</label>
                <input type="text" name="button_text" value="{{ old('button_text', $section->button_text) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button Link URL</label>
                <input type="text" name="button_url" value="{{ old('button_url', $section->button_url) }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Section Image Asset</label>
                <input type="file" name="image" class="form-control bg-light">
                @if($section->image)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ asset('storage/' . $section->image) }}" alt="Section Image" class="rounded border" style="max-height: 60px;">
                        <span class="small text-muted">Current Section Image</span>
                    </div>
                @endif
            </div>

            @if($section->section_key === 'about_summary')
                <h5 class="fw-bold text-primary pt-3 mb-0 border-top"><i class="bi bi-bullseye me-1"></i> Mission & Vision Sub-cards</h5>

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Mission Title</label>
                    <input type="text" name="mission_title" value="{{ old('mission_title', $section->mission_title ?? 'Our Mission') }}" class="form-control bg-light">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Mission Icon Class</label>
                    <input type="text" name="mission_icon" value="{{ old('mission_icon', $section->mission_icon ?? 'bi-bullseye') }}" class="form-control bg-light">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Vision Title</label>
                    <input type="text" name="vision_title" value="{{ old('vision_title', $section->vision_title ?? 'Our Vision') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Mission Description</label>
                    <textarea name="mission_description" rows="2" class="form-control bg-light">{{ old('mission_description', $section->mission_description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Vision Description</label>
                    <textarea name="vision_description" rows="2" class="form-control bg-light">{{ old('vision_description', $section->vision_description) }}</textarea>
                </div>

                <h5 class="fw-bold text-primary pt-3 mb-0 border-top"><i class="bi bi-bank2 me-1"></i> Institutional Governance Sub-card</h5>

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Governance Card Title</label>
                    <input type="text" name="governance_title" value="{{ old('governance_title', $section->governance_title ?? 'Institutional Governance') }}" class="form-control bg-light">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Governance Subtitle</label>
                    <input type="text" name="governance_subtitle" value="{{ old('governance_subtitle', $section->governance_subtitle ?? 'Regulated Micro-Finance ERP') }}" class="form-control bg-light">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Governance Icon Class</label>
                    <input type="text" name="governance_icon" value="{{ old('governance_icon', $section->governance_icon ?? 'bi-bank2') }}" class="form-control bg-light">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold text-secondary">Governance Description</label>
                    <textarea name="governance_description" rows="2" class="form-control bg-light">{{ old('governance_description', $section->governance_description ?? 'Operating under central banking regulation and double-entry accounting integrity.') }}</textarea>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-secondary mb-0">Governance Bullet Points</label>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="addGovBulletBtn">
                            <i class="bi bi-plus-lg me-1"></i> Add Bullet Point
                        </button>
                    </div>
                    <div id="govBulletsContainer" class="d-flex flex-column gap-2">
                        @php
                            $existingBullets = old('governance_bullets', $section->governance_bullets ?? [
                                'Double-entry general ledger audited financial accounting',
                                'Field officer GPS biometric KYC identification',
                                'Central vault limit controls and instant digital receipts'
                            ]);
                            if (!is_array($existingBullets)) {
                                $existingBullets = [];
                            }
                        @endphp
                        @forelse($existingBullets as $idx => $bVal)
                            <div class="input-group input-group-sm gov-bullet-row">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-check-circle-fill"></i></span>
                                <input type="text" name="governance_bullets[]" value="{{ $bVal }}" class="form-control bg-light" placeholder="Enter bullet point text...">
                                <button type="button" class="btn btn-outline-danger remove-bullet-btn" title="Remove Point"><i class="bi bi-trash"></i></button>
                            </div>
                        @empty
                            <div class="input-group input-group-sm gov-bullet-row">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-check-circle-fill"></i></span>
                                <input type="text" name="governance_bullets[]" value="" class="form-control bg-light" placeholder="Enter bullet point text...">
                                <button type="button" class="btn btn-outline-danger remove-bullet-btn" title="Remove Point"><i class="bi bi-trash"></i></button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const container = document.getElementById('govBulletsContainer');
                    const addBtn = document.getElementById('addGovBulletBtn');

                    if (addBtn && container) {
                        addBtn.addEventListener('click', function() {
                            const newRow = document.createElement('div');
                            newRow.className = 'input-group input-group-sm gov-bullet-row';
                            newRow.innerHTML = `
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-check-circle-fill"></i></span>
                                <input type="text" name="governance_bullets[]" value="" class="form-control bg-light" placeholder="Enter bullet point text...">
                                <button type="button" class="btn btn-outline-danger remove-bullet-btn" title="Remove Point"><i class="bi bi-trash"></i></button>
                            `;
                            container.appendChild(newRow);
                        });

                        container.addEventListener('click', function(e) {
                            if (e.target.closest('.remove-bullet-btn')) {
                                const row = e.target.closest('.gov-bullet-row');
                                if (container.querySelectorAll('.gov-bullet-row').length > 1) {
                                    row.remove();
                                } else {
                                    row.querySelector('input').value = '';
                                }
                            }
                        });
                    }
                });
                </script>
            @endif

            @if($section->section_key === 'homepage_cta' || str_contains($section->section_key, 'cta'))
                <h5 class="fw-bold text-primary pt-3 mb-0 border-top"><i class="bi bi-megaphone me-1"></i> CTA Section Settings</h5>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">CTA Heading</label>
                    <input type="text" name="cta_heading" value="{{ old('cta_heading', $section->cta_heading ?? 'Ready to Apply for Micro-Credit?') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">CTA Background Style</label>
                    <select name="cta_bg_style" class="form-select bg-light">
                        <option value="primary" {{ old('cta_bg_style', $section->cta_bg_style ?? 'primary') === 'primary' ? 'selected' : '' }}>Primary Blue Gradient</option>
                        <option value="dark" {{ old('cta_bg_style', $section->cta_bg_style ?? '') === 'dark' ? 'selected' : '' }}>Dark Corporate</option>
                        <option value="success" {{ old('cta_bg_style', $section->cta_bg_style ?? '') === 'success' ? 'selected' : '' }}>Success Green</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold text-secondary">CTA Description</label>
                    <textarea name="cta_description" rows="2" class="form-control bg-light">{{ old('cta_description', $section->cta_description ?? 'Submit your initial loan request online in minutes, or visit your nearest branch counter today.') }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Button 1 Text</label>
                    <input type="text" name="cta_button1_text" value="{{ old('cta_button1_text', $section->cta_button1_text ?? 'Apply for Loan Now') }}" class="form-control bg-light">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Button 1 URL</label>
                    <input type="text" name="cta_button1_url" value="{{ old('cta_button1_url', $section->cta_button1_url ?? '/apply-loan') }}" class="form-control bg-light">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Button 2 Text</label>
                    <input type="text" name="cta_button2_text" value="{{ old('cta_button2_text', $section->cta_button2_text ?? 'Contact Customer Support') }}" class="form-control bg-light">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Button 2 URL</label>
                    <input type="text" name="cta_button2_url" value="{{ old('cta_button2_url', $section->cta_button2_url ?? '/contact') }}" class="form-control bg-light">
                </div>
            @endif

            @if($section->section_key === 'headquarters_branch' || str_contains($section->section_key, 'branch') || str_contains($section->section_key, 'headquarter'))
                <h5 class="fw-bold text-primary pt-3 mb-0 border-top"><i class="bi bi-geo-alt me-1"></i> Headquarters & Support Box Settings</h5>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Head Office Title</label>
                    <input type="text" name="head_office_title" value="{{ old('head_office_title', $section->head_office_title ?? 'TG Microfinance Headquarters') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Head Office Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $section->phone ?? '+91 (800) 555-0199') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Head Office Email</label>
                    <input type="email" name="email" value="{{ old('email', $section->email ?? 'info@tgmicrofinance.org') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Head Office Address</label>
                    <input type="text" name="address" value="{{ old('address', $section->address ?? '100 Financial Avenue, Suite 500') }}" class="form-control bg-light">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Support Box Title</label>
                    <input type="text" name="support_box_title" value="{{ old('support_box_title', $section->support_box_title ?? 'Direct Inquiries & Assistance') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Support Box Button Text</label>
                    <input type="text" name="support_button_text" value="{{ old('support_button_text', $section->support_button_text ?? 'Contact Support Team') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Support Box Button URL</label>
                    <input type="text" name="support_button_url" value="{{ old('support_button_url', $section->support_button_url ?? '/contact') }}" class="form-control bg-light">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Support Box Description</label>
                    <textarea name="support_box_description" rows="2" class="form-control bg-light">{{ old('support_box_description', $section->support_box_description ?? 'Our team is available to guide you through loan applications and account setups.') }}</textarea>
                </div>
            @endif

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Homepage Section
                </button>
                <a href="{{ route('admin.cms.homepage.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
