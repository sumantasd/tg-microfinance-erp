<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use App\Services\DeveloperAuthService;
use App\Services\DeveloperDataManagementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeveloperDataController extends Controller
{
    public function __construct(
        protected DeveloperAuthService $developerAuthService,
        protected DeveloperDataManagementService $dataService,
        protected DatabaseBackupService $backupService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Display the simplified Data Management dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators have access to Data Management.');
        }

        $overview = $this->dataService->getSystemDataOverview();
        $modules = $this->dataService->getAvailableModules();
        $backups = $this->backupService->getBackups();

        return view('admin.system.developer-data.index', compact(
            'overview',
            'modules',
            'backups'
        ));
    }

    /**
     * Trigger manual verified full database backup.
     */
    public function createBackup(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators can create database backups.');
        }

        try {
            $backup = $this->backupService->createVerifiedBackup();

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('success', "Database backup created successfully: {$backup['filename']} ({$backup['size_formatted']}).");
        } catch (Exception $e) {
            Log::error("Manual backup from Data Management failed: " . $e->getMessage());

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', "Backup failed: " . $e->getMessage());
        }
    }

    /**
     * Download a specific backup file safely from private server storage.
     */
    public function downloadBackup(Request $request, string $filename): BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators can download database backups.');
        }

        try {
            return $this->backupService->downloadBackup($filename);
        } catch (Exception $e) {
            Log::error("Backup download failed: " . $e->getMessage());

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', "Download failed: " . $e->getMessage());
        }
    }

    /**
     * Delete a specific SQL backup file, protected by server-side password confirmation.
     */
    public function destroyBackup(Request $request, string $filename): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators can delete database backups.');
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if ($this->developerAuthService->isRateLimited($request)) {
            $seconds = $this->developerAuthService->getRateLimitRemainingSeconds($request);
            $msg = "Too many failed password attempts. Rate limit active. Please try again in {$seconds} seconds.";
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 429);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
        }

        if (!$this->developerAuthService->verifyCredentials($request->password, $request)) {
            $msg = 'Invalid deletion password. Backup deletion aborted.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
        }

        try {
            $this->backupService->deleteBackup($filename);
            $this->developerAuthService->revokeSession($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Backup file '{$filename}' deleted successfully.",
                ]);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('success', "Database backup '{$filename}' deleted successfully.");
        } catch (Exception $e) {
            Log::error("Failed to delete database backup: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['error' => "Delete failed: " . $e->getMessage()], 500);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', "Delete failed: " . $e->getMessage());
        }
    }

    /**
     * Restore database from a selected backup file.
     * Protected by the separate Data Management Password and mandatory pre-restore safety backup.
     */
    public function restore(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators can restore database backups.');
        }

        $request->validate([
            'filename' => 'required|string',
            'password' => 'required|string',
            'confirm_replace' => 'required|accepted',
        ]);

        if ($this->developerAuthService->isRateLimited($request)) {
            $seconds = $this->developerAuthService->getRateLimitRemainingSeconds($request);
            $msg = "Too many failed password attempts. Rate limit active. Please try again in {$seconds} seconds.";
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 429);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
        }

        if (!$this->developerAuthService->verifyCredentials($request->password, $request)) {
            $msg = 'Invalid Data Management Password. Database restoration aborted.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
        }

        try {
            $result = $this->backupService->restoreBackup($request->filename);

            $this->developerAuthService->revokeSession($request);

            // Audit log
            $this->activityLogService->log(
                event: 'database_restored',
                model: $user,
                oldValues: ['restored_backup' => $request->filename],
                newValues: [
                    'pre_restore_backup' => $result['pre_restore_backup'],
                    'restored_at' => now()->toIso8601String(),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Database restored successfully from '{$request->filename}'. Safety pre-restore backup created: '{$result['pre_restore_backup']}'.",
                    'details' => $result,
                ]);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('success', "Database successfully restored from '{$request->filename}'. Safety pre-restore backup created: '{$result['pre_restore_backup']}'.");
        } catch (Exception $e) {
            Log::error("Database restore failed: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get non-destructive preview of selected categories with table breakdown and dependencies.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $selectedModules = $request->input('modules', []);

        if (is_string($selectedModules)) {
            $selectedModules = array_filter(explode(',', $selectedModules));
        }

        $preview = $this->dataService->getCleanupPreview($selectedModules);

        return response()->json($preview);
    }

    /**
     * Execute selective data deletion with password verification and pre-cleanup backup.
     */
    public function execute(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden: Only Super Administrators can perform data cleanup.');
        }

        $request->validate([
            'modules' => 'required|array|min:1',
            'password' => 'nullable|string',
            'confirmation_phrase' => 'required|string',
        ]);

        // 1. Password Verification (or active authorized session)
        $isAuthorized = $this->developerAuthService->isAuthorized($request);

        if (!$isAuthorized) {
            if (!$request->filled('password')) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Deletion password is required.'], 422);
                }
                return redirect()->route('admin.system.developer-data.index')->with('error', 'Deletion password is required.');
            }

            if ($this->developerAuthService->isRateLimited($request)) {
                $seconds = $this->developerAuthService->getRateLimitRemainingSeconds($request);
                $msg = "Too many failed password attempts. Rate limit active. Please try again in {$seconds} seconds.";
                if ($request->expectsJson()) {
                    return response()->json(['error' => $msg], 429);
                }
                return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
            }

            $valid = $this->developerAuthService->verifyCredentials($request->password, $request);
            if (!$valid) {
                $msg = 'Invalid deletion password. Operation aborted.';
                if ($request->expectsJson()) {
                    return response()->json(['error' => $msg], 403);
                }
                return redirect()->route('admin.system.developer-data.index')->with('error', $msg);
            }
        }

        // 2. Validate Confirmation Phrase
        $confirmation = trim((string) $request->input('confirmation_phrase', ''));
        $expectedPhrase = config('developer.confirmation_phrase', 'CLEAN DEMO DATA');

        if ($confirmation !== $expectedPhrase) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => "Confirmation phrase mismatch. You must type '{$expectedPhrase}' exactly to confirm.",
                ], 422);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', "Confirmation phrase mismatch.");
        }

        $selectedModules = $request->input('modules', []);

        try {
            // 3. Execute Mandatory Pre-Cleanup Backup + Dependency Deletion in Transaction
            $result = $this->dataService->executeCleanup(
                selectedModuleKeys: $selectedModules,
                confirmationPhrase: $expectedPhrase,
                userId: Auth::id()
            );

            // 4. Revoke session immediately post-cleanup
            $this->developerAuthService->revokeSession($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$result['total_deleted']} records. Full backup created: {$result['backup']['filename']}.",
                    'backup' => $result['backup']['filename'],
                    'details' => $result,
                ]);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('success', "Data deletion completed successfully! Deleted {$result['total_deleted']} records. Full database backup created: {$result['backup']['filename']}.");
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->route('admin.system.developer-data.index')->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error("Data deletion failed and was rolled back: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['error' => "Deletion stopped: " . $e->getMessage()], 500);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', "Deletion failed and was rolled back: " . $e->getMessage());
        }
    }

    /**
     * Authorize session via passphrase (for API / modal compatibility).
     */
    public function authorizeDeveloper(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Forbidden.');
        }

        if ($this->developerAuthService->isRateLimited($request)) {
            $seconds = $this->developerAuthService->getRateLimitRemainingSeconds($request);
            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', "Too many failed attempts. Rate limit active. Please try again in {$seconds} seconds.");
        }

        $request->validate(['passphrase' => 'required|string']);

        $verified = $this->developerAuthService->verifyCredentials($request->passphrase, $request);

        if (!$verified) {
            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', 'Invalid password. Authorization denied.');
        }

        return redirect()
            ->route('admin.system.developer-data.index')
            ->with('success', 'Authorization granted successfully.');
    }

    /**
     * Revoke active session.
     */
    public function revokeAuthorization(Request $request): RedirectResponse
    {
        $this->developerAuthService->revokeSession($request);

        return redirect()
            ->route('admin.system.developer-data.index')
            ->with('info', 'Session locked successfully.');
    }
}
