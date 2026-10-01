<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __construct(protected ActivityLogService $activityLogService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = MediaFile::with(['user', 'branch']);

        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('collection', 'like', "%{$search}%");
            });
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->input('file_type'));
        }

        if ($request->filled('collection')) {
            $query->where('collection', $request->input('collection'));
        }

        $mediaFiles = $query->latest()->paginate(24)->withQueryString();

        return view('admin.media.index', [
            'mediaFiles' => $mediaFiles,
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv',
            'title' => 'nullable|string|max:255',
            'collection' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();

        $fileType = 'document';
        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif (str_contains($mimeType, 'pdf')) {
            $fileType = 'pdf';
        } elseif (str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') || str_contains($mimeType, 'csv')) {
            $fileType = 'spreadsheet';
        }

        $path = $file->store('media/' . date('Y/m'), 'public');

        $mediaFile = MediaFile::create([
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'file_name' => $originalName,
            'file_path' => $path,
            'disk' => 'public',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'title' => $request->input('title') ?: $originalName,
            'collection' => $request->input('collection') ?: 'general',
        ]);

        $this->activityLogService->log('media_uploaded', $mediaFile, null, $mediaFile->toArray());

        return redirect()->route('admin.media.index')->with('success', 'File uploaded successfully.');
    }

    public function download(MediaFile $mediaFile)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $user->company_id !== $mediaFile->company_id) {
            abort(403, 'Unauthorized file access.');
        }

        if (!Storage::disk($mediaFile->disk)->exists($mediaFile->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk($mediaFile->disk)->download($mediaFile->file_path, $mediaFile->file_name);
    }

    public function destroy(MediaFile $mediaFile)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $user->company_id !== $mediaFile->company_id) {
            abort(403, 'Unauthorized operation.');
        }

        $mediaFile->delete();
        $this->activityLogService->log('media_deleted', $mediaFile, $mediaFile->toArray(), null);

        return redirect()->route('admin.media.index')->with('success', 'File deleted successfully.');
    }
}
