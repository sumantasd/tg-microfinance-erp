<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(protected DatabaseBackupService $backupService) {}

    /**
     * Display database backup management dashboard and backup files list.
     */
    public function index(): View
    {
        Gate::authorize('backup.view');

        $backups = $this->backupService->getBackups();

        return view('admin.system.backup.index', compact('backups'));
    }

    /**
     * Create a new SQL database dump backup file.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('backup.create');

        try {
            $backup = $this->backupService->createBackup();

            return redirect()
                ->route('admin.system.backup.index')
                ->with('success', "Database backup created successfully ({$backup['filename']} - {$backup['size_formatted']}).");
        } catch (Exception $e) {
            Log::error("Failed to create database backup: " . $e->getMessage());

            return redirect()
                ->route('admin.system.backup.index')
                ->with('error', "Failed to create database backup: " . $e->getMessage());
        }
    }

    /**
     * Download a specific SQL backup file.
     */
    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('backup.download');

        try {
            return $this->backupService->downloadBackup($filename);
        } catch (Exception $e) {
            Log::error("Failed to download database backup: " . $e->getMessage());

            return redirect()
                ->route('admin.system.backup.index')
                ->with('error', "Download failed: " . $e->getMessage());
        }
    }

    /**
     * Delete a specific SQL backup file from storage.
     */
    public function destroy(string $filename): RedirectResponse
    {
        Gate::authorize('backup.delete');

        try {
            $this->backupService->deleteBackup($filename);

            return redirect()
                ->route('admin.system.backup.index')
                ->with('success', "Database backup '{$filename}' deleted successfully.");
        } catch (Exception $e) {
            Log::error("Failed to delete database backup: " . $e->getMessage());

            return redirect()
                ->route('admin.system.backup.index')
                ->with('error', "Delete failed: " . $e->getMessage());
        }
    }
}
