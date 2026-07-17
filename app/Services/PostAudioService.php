<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class PostAudioService
{
    public const MAX_DURATION_SECONDS = 10;

    public function store(Post $post, UploadedFile $file, float $clientDurationSeconds): array
    {
        $serverDurationSeconds = $this->detectDurationSeconds($file);
        $durationSeconds = $serverDurationSeconds ?? $clientDurationSeconds;

        if ($durationSeconds > self::MAX_DURATION_SECONDS) {
            throw ValidationException::withMessages([
                'audio' => ['La nota audio non puo superare 10 secondi.'],
            ]);
        }

        $disk = config('services.spoton_audio.disk', 'public');
        $directory = trim((string) config('services.spoton_audio.directory', 'post-audios'), '/');
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'm4a';
        $filename = $post->id.'-'.Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, $disk);

        return [
            'audio_disk' => $disk,
            'audio_path' => $path,
            'audio_url' => Storage::disk($disk)->url($path),
            'audio_mime' => $file->getMimeType(),
            'audio_size_bytes' => $file->getSize(),
            'audio_duration_seconds' => (int) ceil($durationSeconds),
        ];
    }

    public function deleteForPost(Post $post): void
    {
        if (! $post->audio_disk || ! $post->audio_path) {
            return;
        }

        Storage::disk($post->audio_disk)->delete($post->audio_path);
    }

    public function emptyPayload(): array
    {
        return [
            'audio_disk' => null,
            'audio_path' => null,
            'audio_url' => null,
            'audio_mime' => null,
            'audio_size_bytes' => null,
            'audio_duration_seconds' => null,
        ];
    }

    private function detectDurationSeconds(UploadedFile $file): ?float
    {
        $which = new Process(['sh', '-lc', 'command -v ffprobe']);
        $which->setTimeout(5);
        $which->run();

        if (! $which->isSuccessful()) {
            return null;
        }

        $ffprobe = trim($which->getOutput());
        if ($ffprobe === '') {
            return null;
        }

        $process = new Process([
            $ffprobe,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $file->getRealPath(),
        ]);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $duration = (float) trim($process->getOutput());

        return $duration > 0 ? $duration : null;
    }
}
