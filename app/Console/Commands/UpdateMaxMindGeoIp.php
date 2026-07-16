<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class UpdateMaxMindGeoIp extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:maxmind-update';

    /**
     * @var string
     */
    protected $description = 'Download and update the MaxMind GeoLite2 City database';

    public function handle(): int
    {
        // Extracting the ~65MB database exceeds the default 128M CLI limit,
        // which killed the process without any error output.
        ini_set('memory_limit', '512M');

        $licenseKey = config('services.maxmind.license_key');

        if (blank($licenseKey)) {
            $this->error('MaxMind license key is missing. Set MAXMIND_LICENSE_KEY in .env');

            return self::FAILURE;
        }

        $targetPath = config('services.maxmind.database_path');
        $tempDir = sys_get_temp_dir().'/maxmind_extract_'.uniqid();
        $tempTarGz = tempnam(sys_get_temp_dir(), 'maxmind_').'.tar.gz';

        try {
            $this->download($licenseKey, $tempTarGz);
            $this->extract($tempTarGz, $tempDir);
            $this->install($tempDir, $targetPath);

            $this->info("MaxMind database updated: {$targetPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to update MaxMind database: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($tempTarGz);
            File::deleteDirectory($tempDir);
        }
    }

    private function download(string $licenseKey, string $tempTarGz): void
    {
        $url = sprintf(
            'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz',
            urlencode($licenseKey)
        );

        $this->info('Downloading latest GeoLite2-City database...');

        Http::sink($tempTarGz)
            ->timeout(120)
            ->get($url)
            ->throw();
    }

    private function extract(string $tempTarGz, string $tempDir): void
    {
        $this->info('Extracting database archive...');

        $archive = new PharData($tempTarGz);
        $archive->extractTo($tempDir);
    }

    private function install(string $tempDir, string $targetPath): void
    {
        $mmdbPath = $this->findMmdbFile($tempDir);

        if ($mmdbPath === null) {
            throw new \RuntimeException('Could not find .mmdb file in downloaded archive.');
        }

        $directory = dirname($targetPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Could not create directory: {$directory}");
        }

        if (! rename($mmdbPath, $targetPath)) {
            throw new \RuntimeException("Could not move database to: {$targetPath}");
        }
    }

    private function findMmdbFile(string $directory): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }
}
