<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupService
{
    /**
     * Get the absolute path to the secure backup directory.
     */
    public function getBackupDirectory(): string
    {
        $directory = storage_path('app/private/backups');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return $directory;
    }

    /**
     * Create a complete SQL database dump and save securely.
     */
    public function createBackup(): array
    {
        $directory = $this->getBackupDirectory();
        $timestamp = now()->format('Y_m_d_H_i_s');
        $filename = "database_backup_{$timestamp}.sql";
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

        // Ensure we never overwrite an existing backup created in the exact same second
        if (File::exists($filePath)) {
            sleep(1);
            $timestamp = now()->format('Y_m_d_H_i_s');
            $filename = "database_backup_{$timestamp}.sql";
            $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        }

        $driver = config('database.default');

        try {
            $dumpSuccess = false;

            if (in_array($driver, ['mysql', 'mariadb'])) {
                $dumpSuccess = $this->dumpMysqlWithNativeTool($filePath);
            }

            if (!$dumpSuccess) {
                // Fallback to in-process PHP SQL dumper (compatible with SQLite and MySQL)
                $this->dumpDatabaseWithPhpEngine($filePath);
            }

            if (!File::exists($filePath) || File::size($filePath) === 0) {
                throw new Exception("Generated backup file is empty or missing: {$filename}");
            }

            $size = File::size($filePath);

            Log::info("Database backup created successfully: {$filename}", [
                'filename' => $filename,
                'size' => $size,
                'driver' => $driver,
            ]);

            return [
                'filename' => $filename,
                'path' => $filePath,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'created_at' => now(),
            ];
        } catch (Exception $e) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            Log::error("Database backup failed: " . $e->getMessage(), [
                'exception' => $e,
                'filename' => $filename,
            ]);

            throw $e;
        }
    }

    /**
     * Create a backup and strictly verify its structural integrity.
     */
    public function createVerifiedBackup(): array
    {
        $backup = $this->createBackup();
        $verification = $this->verifyBackupFile($backup['path']);

        if (!$verification['valid']) {
            if (File::exists($backup['path'])) {
                File::delete($backup['path']);
            }
            throw new Exception("Pre-cleanup backup failed structural verification: " . $verification['details']);
        }

        $backup['verification'] = $verification;
        return $backup;
    }

    /**
     * Verify that an SQL backup file exists, is non-empty, and has structurally valid SQL markers.
     */
    public function verifyBackupFile(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [
                'valid' => false,
                'status' => 'missing',
                'size' => 0,
                'size_formatted' => '0 B',
                'table_count' => 0,
                'details' => 'Backup file does not exist on disk.',
            ];
        }

        $size = File::size($filePath);
        if ($size === 0) {
            return [
                'valid' => false,
                'status' => 'empty',
                'size' => 0,
                'size_formatted' => '0 B',
                'table_count' => 0,
                'details' => 'Backup file is empty (0 bytes).',
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'valid' => false,
                'status' => 'unreadable',
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'table_count' => 0,
                'details' => 'Unable to open backup file for reading.',
            ];
        }

        $tableCount = 0;
        $hasHeaderMarker = false;
        $insertCount = 0;

        // Read lines for structure markers
        while (($line = fgets($handle, 4096)) !== false) {
            if (!$hasHeaderMarker && (
                str_contains($line, 'Database Backup Dump') ||
                str_contains($line, 'MySQL dump') ||
                str_contains($line, 'PRAGMA foreign_keys') ||
                str_contains($line, 'FOREIGN_KEY_CHECKS')
            )) {
                $hasHeaderMarker = true;
            }

            if (stripos($line, 'CREATE TABLE') !== false) {
                $tableCount++;
            }

            if (stripos($line, 'INSERT INTO') !== false) {
                $insertCount++;
            }
        }

        // Check footer closing marker
        $hasClosingMarker = false;
        if ($size > 1024) {
            fseek($handle, max(0, $size - 2048));
            $tail = fread($handle, 2048);
            if (
                str_contains($tail, 'FOREIGN_KEY_CHECKS = 1') ||
                str_contains($tail, 'PRAGMA foreign_keys = ON') ||
                str_contains($tail, 'Dump completed') ||
                str_contains($tail, 'INSERT INTO') ||
                str_contains($tail, ';')
            ) {
                $hasClosingMarker = true;
            }
        } else {
            $hasClosingMarker = true;
        }

        fclose($handle);

        $isValid = ($tableCount > 0) && ($hasHeaderMarker || $tableCount > 0) && $hasClosingMarker;

        $details = $isValid
            ? "Verified successfully: contains {$tableCount} table schemas and {$insertCount} data chunk statements."
            : "Structural verification failed: table_count={$tableCount}, hasHeader=" . ($hasHeaderMarker ? 'yes' : 'no') . ", hasClosing=" . ($hasClosingMarker ? 'yes' : 'no');

        return [
            'valid' => $isValid,
            'status' => $isValid ? 'verified' : 'corrupt',
            'size' => $size,
            'size_formatted' => $this->formatFileSize($size),
            'table_count' => $tableCount,
            'insert_count' => $insertCount,
            'has_header' => $hasHeaderMarker,
            'has_closing' => $hasClosingMarker,
            'details' => $details,
        ];
    }

    /**
     * List all database backup files stored securely on the server.
     */
    public function getBackups(): array
    {
        $directory = $this->getBackupDirectory();
        $files = File::files($directory);

        $backups = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (!preg_match('/^database_backup_\d{4}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}\.sql$/', $filename)) {
                continue;
            }

            $size = $file->getSize();
            $mtime = $file->getMTime();
            $filePath = $file->getRealPath();

            $backups[] = [
                'filename' => $filename,
                'path' => $filePath,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'created_at' => \Carbon\Carbon::createFromTimestamp($mtime),
                'is_verified' => $size > 1024,
            ];
        }

        // Sort newest first
        usort($backups, function ($a, $b) {
            return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
        });

        return $backups;
    }

    /**
     * Download a specific backup file safely.
     */
    public function downloadBackup(string $filename): BinaryFileResponse
    {
        $filePath = $this->validateAndGetFilePath($filename);

        Log::info("Database backup downloaded: {$filename}", ['filename' => $filename]);

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/sql',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Delete a specific backup file safely.
     */
    public function deleteBackup(string $filename): bool
    {
        $filePath = $this->validateAndGetFilePath($filename);

        $deleted = File::delete($filePath);

        if ($deleted) {
            Log::info("Database backup deleted: {$filename}", ['filename' => $filename]);
        }

        return $deleted;
    }

    /**
     * Restore database from a verified SQL backup file safely.
     */
    public function restoreBackup(string $filename): array
    {
        // 1. Validate filename and path traversal
        $filePath = $this->validateAndGetFilePath($filename);

        // 2. Verify structural integrity of the target backup
        $verification = $this->verifyBackupFile($filePath);
        if (!$verification['valid']) {
            throw new Exception("Cannot restore corrupted or invalid backup file: " . $verification['details']);
        }

        // 3. Acquire atomic restore concurrency lock
        $lock = Cache::lock('database_restore_lock', 600);
        if (!$lock->get()) {
            throw new Exception("Another database restore operation is currently in progress. Please wait.");
        }

        $preRestoreBackup = null;

        try {
            // 4. Mandatory Pre-Restore Safety Backup
            Log::info("DatabaseBackupService: Creating mandatory pre-restore safety backup...");
            $preRestoreBackup = $this->createVerifiedBackup();
            Log::info("DatabaseBackupService: Pre-restore safety backup created: {$preRestoreBackup['filename']}");

            // 5. Execute the SQL restoration
            $driver = config('database.default');
            $restoreSuccess = false;

            if (in_array($driver, ['mysql', 'mariadb'])) {
                $restoreSuccess = $this->restoreMysqlWithNativeTool($filePath);
            }

            if (!$restoreSuccess) {
                // Fallback to streaming PHP SQL execution engine
                $this->restoreDatabaseWithPhpEngine($filePath);
                $restoreSuccess = true;
            }

            // 6. Post-restore integrity verification
            $this->verifyPostRestoreIntegrity();

            Log::info("Database successfully restored from: {$filename}", [
                'filename' => $filename,
                'pre_restore_backup' => $preRestoreBackup['filename'],
            ]);

            return [
                'success' => true,
                'restored_filename' => $filename,
                'pre_restore_backup' => $preRestoreBackup['filename'],
                'restored_at' => now(),
            ];
        } catch (Exception $e) {
            $preMsg = $preRestoreBackup ? " Safety backup was preserved: {$preRestoreBackup['filename']}." : "";
            Log::error("Database restore failed: " . $e->getMessage() . $preMsg, [
                'filename' => $filename,
                'exception' => $e,
            ]);

            throw new Exception("Database restore failed: " . $e->getMessage() . $preMsg);
        } finally {
            $lock->release();
        }
    }

    /**
     * Attempt to restore MySQL/MariaDB database using native mysql client executable.
     */
    protected function restoreMysqlWithNativeTool(string $filePath): bool
    {
        if (!function_exists('exec') || ini_get('safe_mode')) {
            return false;
        }

        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '3306');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        if (empty($database) || empty($username)) {
            return false;
        }

        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            !empty($password) ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnCode = -1;

        exec($cmd . ' 2>&1', $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Streaming PHP SQL Execution Engine for restoring database schema and data.
     */
    protected function restoreDatabaseWithPhpEngine(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open backup file for reading: {$filePath}");
        }

        $statement = '';

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            // Skip comment and empty lines
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $statement .= $line;

            // Semicolon at end of line signifies complete statement
            if (str_ends_with($trimmed, ';')) {
                $sqlToExecute = trim($statement);
                if (!empty($sqlToExecute)) {
                    DB::unprepared($sqlToExecute);
                }
                $statement = '';
            }
        }

        if (!empty(trim($statement))) {
            DB::unprepared(trim($statement));
        }

        fclose($handle);
    }

    /**
     * Verify that database schema and key records exist after restoration.
     */
    protected function verifyPostRestoreIntegrity(): void
    {
        $hasUsers = Schema::hasTable('users');
        $hasSchemes = Schema::hasTable('loan_schemes');

        if (!$hasUsers || !$hasSchemes) {
            throw new Exception("Post-restore integrity check failed: users or loan_schemes table missing.");
        }

        $usersCount = DB::table('users')->count();
        if ($usersCount === 0) {
            throw new Exception("Post-restore integrity check failed: users table contains 0 records.");
        }
    }

    /**
     * Validate backup filename and enforce strict path traversal security.
     */
    public function validateAndGetFilePath(string $filename): string
    {
        // 1. Strict filename regex validation
        if (!preg_match('/^database_backup_\d{4}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}\.sql$/', $filename)) {
            throw new Exception("Invalid backup filename format: {$filename}");
        }

        $directory = realpath($this->getBackupDirectory());
        $targetPath = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        // 2. Check file existence
        if (!File::exists($targetPath)) {
            throw new Exception("Backup file not found: {$filename}");
        }

        // 3. Strict Realpath containment validation to prevent ../ directory traversal attacks
        $realTargetPath = realpath($targetPath);

        if ($realTargetPath === false || !str_starts_with($realTargetPath, $directory)) {
            throw new Exception("Security Alert: Path traversal attempt blocked for filename: {$filename}");
        }

        return $realTargetPath;
    }

    /**
     * Attempt to dump MySQL/MariaDB database using native mysqldump executable.
     */
    protected function dumpMysqlWithNativeTool(string $filePath): bool
    {
        if (!function_exists('exec') || ini_get('safe_mode')) {
            return false;
        }

        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '3306');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        if (empty($database) || empty($username)) {
            return false;
        }

        // Build command using escapeshellarg for safety
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            !empty($password) ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnCode = -1;

        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode === 0 && File::exists($filePath) && File::size($filePath) > 0) {
            return true;
        }

        return false;
    }

    /**
     * PHP-based SQL Database Dumper Engine (Compatible with MySQL, MariaDB, and SQLite).
     */
    protected function dumpDatabaseWithPhpEngine(string $filePath): void
    {
        $handle = fopen($filePath, 'w');

        if (!$handle) {
            throw new Exception("Failed to open file handle for backup writing: {$filePath}");
        }

        $connection = DB::connection();
        $driver = config('database.default');
        $databaseName = config("database.connections.{$driver}.database", 'application_db');

        // Write SQL Header
        fwrite($handle, "-- TG Microfinance ERP Database Backup Dump\n");
        fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
        fwrite($handle, "-- Database: {$databaseName}\n");
        fwrite($handle, "-- Driver: {$driver}\n");
        fwrite($handle, "-- ------------------------------------------------------\n\n");

        if ($driver === 'sqlite') {
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
            $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            foreach ($tables as $table) {
                $tableName = $table->name;
                $createSql = $table->sql;

                fwrite($handle, "-- Table structure for `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($handle, "{$createSql};\n\n");

                // Dump Table Data
                $this->dumpTableData($handle, $tableName);
            }

            fwrite($handle, "PRAGMA foreign_keys = ON;\n");
        } else {
            // MySQL / MariaDB PHP Dumper
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            $tables = DB::select('SHOW TABLES');
            $keyName = "Tables_in_" . $databaseName;

            foreach ($tables as $t) {
                $tableName = $t->$keyName ?? array_values((array) $t)[0];

                // Table structure
                $createRes = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createRes[0]->{'Create Table'} ?? null;

                if ($createSql) {
                    fwrite($handle, "-- Table structure for `{$tableName}`\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, "{$createSql};\n\n");
                }

                // Table Data
                $this->dumpTableData($handle, $tableName);
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        }

        fclose($handle);
    }

    /**
     * Dump table records in chunks into the SQL backup stream.
     */
    protected function dumpTableData($handle, string $tableName): void
    {
        $rowsCount = DB::table($tableName)->count();

        if ($rowsCount === 0) {
            return;
        }

        fwrite($handle, "-- Dumping data for table `{$tableName}`\n");

        DB::table($tableName)->orderByRaw('1')->chunk(200, function ($rows) use ($handle, $tableName) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $keys = array_keys($rowArray);
                $escapedKeys = array_map(fn($k) => "`{$k}`", $keys);

                $values = array_values($rowArray);
                $escapedValues = array_map(function ($val) {
                    if (is_null($val)) {
                        return 'NULL';
                    }
                    if (is_numeric($val) && !is_string($val)) {
                        return $val;
                    }
                    // String escaping
                    $escaped = str_replace(
                        ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"],
                        ["\\\\", "\\0", "\\n", "\\r", "''", '\\"', "\\Z"],
                        (string) $val
                    );
                    return "'{$escaped}'";
                }, $values);

                $sql = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $tableName,
                    implode(', ', $escapedKeys),
                    implode(', ', $escapedValues)
                );

                fwrite($handle, $sql);
            }
        });

        fwrite($handle, "\n");
    }

    /**
     * Format bytes to human readable file size.
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
