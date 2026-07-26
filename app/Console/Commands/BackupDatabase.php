<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ifsnop\Mysqldump\Mysqldump;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Dump the database (pure PHP, no exec required) and upload it to S3';

    public function handle(): int
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);

        $timestamp = now()->format('Ymd_His');
        $localPath = storage_path("app/backups/backup_{$timestamp}.sql");
        $gzPath = "{$localPath}.gz";

        if (! is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        $this->info('Starting database dump (pure PHP, no shell exec)...');

        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

            $dump = new Mysqldump($dsn, $dbUser, $dbPass, [
                'add-drop-table'      => false,
                'single-transaction'  => true, // consistent snapshot without locking, same as --single-transaction
                'lock-tables'         => false,
                'add-locks'           => false,
                'skip-comments'       => true,
            ]);

            $dump->start($localPath);
        } catch (\Exception $e) {
            $this->error('Dump failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! file_exists($localPath) || filesize($localPath) === 0) {
            $this->error('Dump produced an empty file.');
            return self::FAILURE;
        }

        $tableCount = substr_count(file_get_contents($localPath), 'CREATE TABLE');
        if ($tableCount < 5) {
            $this->error("Dump looks incomplete — only {$tableCount} tables found. Aborting upload.");
            unlink($localPath);
            return self::FAILURE;
        }

        $this->info("Dump complete: {$tableCount} tables, " . round(filesize($localPath) / 1024 / 1024, 1) . " MB.");

        // Compress — stream through in chunks rather than loading the
        // whole file into memory at once, since a 76MB+ dump read via
        // file_get_contents() could strain a shared-hosting PHP memory limit.
        $this->info('Compressing...');
        $this->gzipFile($localPath, $gzPath);
        unlink($localPath);

        $this->info('Uploading to S3...');
        $s3Key = "db-backups/backup_{$timestamp}.sql.gz";
        $stream = fopen($gzPath, 'r');
        $uploaded = Storage::disk('s3')->put($s3Key, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $uploaded) {
            $this->error('S3 upload failed.');
            return self::FAILURE;
        }

        unlink($gzPath);
        $this->info("Backup uploaded: {$s3Key}");

        $this->pruneOldBackups();

        return self::SUCCESS;
    }


    protected function gzipFile(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb9');

        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 512)); // 512KB chunks
        }

        fclose($in);
        gzclose($out);
    }

    protected function pruneOldBackups(): void
    {
        $files = Storage::disk('s3')->files('db-backups');
        $cutoff = now()->subDays(14);

        foreach ($files as $file) {
            $lastModified = Storage::disk('s3')->lastModified($file);

            if ($lastModified && \Carbon\Carbon::createFromTimestamp($lastModified)->lt($cutoff)) {
                Storage::disk('s3')->delete($file);
                $this->info("Pruned old backup: {$file}");
            }
        }
    }
}
