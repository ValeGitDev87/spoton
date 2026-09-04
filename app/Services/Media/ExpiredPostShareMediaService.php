<?php

namespace App\Services\Media;

use App\Models\PostShareMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ExpiredPostShareMediaService
{
    public function __construct(private readonly PostShareMediaService $media) {}

    public function query(): Builder
    {
        return PostShareMedia::query()
            ->with(['post.location'])
            ->expiredOrUnavailable()
            ->orderBy('expires_at');
    }

    /**
     * @return array{records: int, files: int, bytes: int}
     */
    public function summary(): array
    {
        $items = $this->query()->get();
        $files = $items->filter(fn (PostShareMedia $item) => $this->fileExists($item));

        return [
            'records' => $items->count(),
            'files' => $files->count(),
            'bytes' => (int) $files->sum('size_bytes'),
        ];
    }

    /**
     * @return array{records: int, files: int, bytes: int}
     */
    public function purge(): array
    {
        $result = ['records' => 0, 'files' => 0, 'bytes' => 0];

        $this->query()->get()->each(function (PostShareMedia $item) use (&$result): void {
            if (! $this->isStillPurgeable($item)) {
                return;
            }

            $fileExists = $this->fileExists($item);
            $bytes = (int) ($item->size_bytes ?? 0);
            $this->media->deleteStoredFile($item);
            $item->delete();

            $result['records']++;
            if ($fileExists) {
                $result['files']++;
                $result['bytes'] += $bytes;
            }
        });

        return $result;
    }

    public function fileStatus(PostShareMedia $media): string
    {
        if (! $media->path) {
            return 'Nessun file';
        }

        return $this->fileExists($media) ? 'Presente' : 'Mancante';
    }

    public function fileExists(PostShareMedia $media): bool
    {
        return is_string($media->disk)
            && is_string($media->path)
            && Storage::disk($media->disk)->exists($media->path);
    }

    private function isStillPurgeable(PostShareMedia $media): bool
    {
        $fresh = $media->fresh(['post.location']);

        if (! $fresh) {
            return false;
        }

        return $fresh->expires_at?->isPast()
            || ! $fresh->post
            || ! $fresh->post->isActive()
            || ! $fresh->post->location?->isPubliclyVisible();
    }
}
