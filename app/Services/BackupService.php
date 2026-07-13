<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Process\Process;

class BackupService
{
    public function path(): string
    {
        return (string) config('services.spoton_backup.path', '/var/backups/spotonapp');
    }

    public function list(): Collection
    {
        if (! File::isDirectory($this->path())) {
            return collect();
        }

        return collect(File::files($this->path()))
            ->filter(fn (SplFileInfo $file) => $this->isAllowedFilename($file->getFilename()))
            ->map(fn (SplFileInfo $file) => [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'size_human' => $this->humanSize($file->getSize()),
                'modified_at' => $file->getMTime(),
                'modified_at_human' => date('d/m/Y H:i:s', $file->getMTime()),
            ])
            ->sortByDesc('modified_at')
            ->values();
    }

    public function resolve(string $filename): string
    {
        $filename = basename($filename);

        if (! $this->isAllowedFilename($filename)) {
            throw new InvalidArgumentException('Backup non valido.');
        }

        $path = $this->path().DIRECTORY_SEPARATOR.$filename;

        if (! File::isFile($path)) {
            throw new InvalidArgumentException('Backup non trovato.');
        }

        return $path;
    }

    public function createManualBackup(): array
    {
        $command = (string) config('services.spoton_backup.command');

        if ($command === '' || ! File::exists($command)) {
            throw new InvalidArgumentException('Comando backup non configurato.');
        }

        $process = new Process([$command]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new InvalidArgumentException(trim($process->getErrorOutput()) ?: 'Backup manuale non riuscito.');
        }

        return [
            'output' => trim($process->getOutput()),
        ];
    }

    private function isAllowedFilename(string $filename): bool
    {
        return preg_match('/^spotonapp_db_[A-Za-z0-9_.-]+\.dump$/', $filename) === 1;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }
}
