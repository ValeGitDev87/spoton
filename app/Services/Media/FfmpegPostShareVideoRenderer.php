<?php

namespace App\Services\Media;

use App\Contracts\PostShareVideoRenderer;
use App\Models\PostShareMedia;
use App\Support\PostCategory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class FfmpegPostShareVideoRenderer implements PostShareVideoRenderer
{
    public function __construct(private readonly PublicPostMediaContent $publicContent) {}

    public function render(PostShareMedia $media): array
    {
        $media->loadMissing(['post.author', 'post.location', 'post.mentions']);
        $post = $media->post;

        if (! $post || ! $post->audio_disk || ! $post->audio_path) {
            throw new RuntimeException('Audio sorgente non disponibile.');
        }

        $tempDirectory = storage_path('app/share-video-temp/'.$media->id);
        $storedDisk = null;
        $storedPath = null;
        File::ensureDirectoryExists($tempDirectory);

        try {
            $audioPath = $tempDirectory.'/source-audio';
            $this->copyToLocalPath($post->audio_disk, $post->audio_path, $audioPath);

            $textFiles = $this->writeTextFiles($tempDirectory, $media);
            $outputPath = $tempDirectory.'/share-video.mp4';
            $this->runFfmpeg($audioPath, $outputPath, $textFiles, $media);

            $disk = (string) config('spoton.share_video.disk', 'local');
            $directory = trim((string) config('spoton.share_video.directory', 'share-videos'), '/');
            $targetPath = $directory.'/'.$media->id.'-'.$media->template_version.'.mp4';
            $storedDisk = $disk;
            $storedPath = $targetPath;
            $stream = fopen($outputPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('Video generato non leggibile.');
            }

            try {
                if (! Storage::disk($disk)->put($targetPath, $stream)) {
                    throw new RuntimeException('Video generato non salvato.');
                }
            } finally {
                fclose($stream);
            }

            return [
                'disk' => $disk,
                'path' => $targetPath,
                'mime' => 'video/mp4',
                'size_bytes' => (int) Storage::disk($disk)->size($targetPath),
            ];
        } catch (Throwable $exception) {
            if ($storedDisk && $storedPath) {
                Storage::disk($storedDisk)->delete($storedPath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    /**
     * @param  array{author: string, category: string, location: string, text: string}  $textFiles
     */
    private function runFfmpeg(
        string $audioPath,
        string $outputPath,
        array $textFiles,
        PostShareMedia $media,
    ): void {
        $post = $media->post;
        $binary = (string) config('spoton.share_video.ffmpeg_binary', 'ffmpeg');
        $font = (string) config('spoton.share_video.font_path', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
        $fontBold = (string) config('spoton.share_video.font_bold_path', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf');
        $logo = public_path('images/share/spoton-symbol.png');
        $width = (int) config('spoton.share_video.width', 720);
        $height = (int) config('spoton.share_video.height', 1280);
        $fps = (int) config('spoton.share_video.fps', 25);
        $duration = min(10, max(1, (int) ($post->audio_duration_seconds ?: 10)));

        foreach ([$font, $fontBold, $logo] as $requiredFile) {
            if (! is_file($requiredFile)) {
                throw new RuntimeException('Risorsa video non disponibile.');
            }
        }

        $regularFont = $this->filterPath($font);
        $boldFont = $this->filterPath($fontBold);
        $authorFile = $this->filterPath($textFiles['author']);
        $categoryFile = $this->filterPath($textFiles['category']);
        $locationFile = $this->filterPath($textFiles['location']);
        $textFile = $this->filterPath($textFiles['text']);
        $filter = implode(';', [
            '[1:a]asplit=2[audioout][waveaudio]',
            "[waveaudio]showwaves=s=600x220:mode=line:colors=0x38cfd0:r={$fps},format=rgba[wave]",
            '[0:v][wave]overlay=60:520:shortest=1[wavebase]',
            '[2:v]scale=92:92:force_original_aspect_ratio=decrease[logo]',
            '[wavebase][logo]overlay=58:62[brandbase]',
            "[brandbase]drawtext=fontfile='{$boldFont}':text='SpotOn':expansion=none:fontcolor=white:fontsize=38:x=168:y=80[brand]",
            "[brand]drawtext=fontfile='{$boldFont}':textfile='{$categoryFile}':expansion=none:fontcolor=0xffd44d:fontsize=24:x=60:y=230[category]",
            "[category]drawtext=fontfile='{$boldFont}':textfile='{$authorFile}':expansion=none:fontcolor=white:fontsize=34:x=60:y=285[author]",
            "[author]drawtext=fontfile='{$regularFont}':textfile='{$textFile}':expansion=none:fontcolor=white:fontsize=30:line_spacing=12:x=60:y=350[text]",
            "[text]drawtext=fontfile='{$regularFont}':textfile='{$locationFile}':expansion=none:fontcolor=0xd1d5db:fontsize=25:x=60:y=810[location]",
            "[location]drawtext=fontfile='{$boldFont}':text='Ascolta e scopri su SpotOn':expansion=none:fontcolor=white:fontsize=27:x=60:y=1120[v]",
        ]);

        $process = new Process([
            $binary,
            '-hide_banner',
            '-loglevel',
            'error',
            '-y',
            '-f',
            'lavfi',
            '-i',
            "color=c=0x111827:s={$width}x{$height}:r={$fps}:d={$duration}",
            '-i',
            $audioPath,
            '-loop',
            '1',
            '-i',
            $logo,
            '-filter_complex',
            $filter,
            '-map',
            '[v]',
            '-map',
            '[audioout]',
            '-t',
            (string) $duration,
            '-r',
            (string) $fps,
            '-c:v',
            'libx264',
            '-preset',
            'veryfast',
            '-crf',
            '24',
            '-pix_fmt',
            'yuv420p',
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            '-movflags',
            '+faststart',
            $outputPath,
        ]);
        $process->setTimeout((int) config('spoton.share_video.timeout', 60));
        $process->run();

        if (! $process->isSuccessful() || ! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('FFmpeg non ha completato il video.');
        }
    }

    /**
     * @return array{author: string, category: string, location: string, text: string}
     */
    private function writeTextFiles(string $directory, PostShareMedia $media): array
    {
        $post = $media->post;
        $values = [
            'author' => $this->singleLine($this->publicContent->author($post), 42),
            'category' => $this->singleLine(PostCategory::label($post->category), 42),
            'location' => $this->singleLine($post->location->name.', '.$post->location->city, 62),
            'text' => $this->wrappedText($this->publicContent->text($post)),
        ];

        foreach ($values as $key => $value) {
            $path = $directory.'/'.$key.'.txt';
            File::put($path, $value);
            $values[$key] = $path;
        }

        return $values;
    }

    private function copyToLocalPath(string $disk, string $path, string $target): void
    {
        $source = Storage::disk($disk)->readStream($path);

        if ($source === false) {
            throw new RuntimeException('Audio sorgente non leggibile.');
        }

        $destination = fopen($target, 'wb');

        if ($destination === false) {
            fclose($source);
            throw new RuntimeException('File temporaneo non scrivibile.');
        }

        try {
            stream_copy_to_stream($source, $destination);
        } finally {
            fclose($source);
            fclose($destination);
        }
    }

    private function singleLine(?string $value, int $limit): string
    {
        return Str::limit(preg_replace('/\s+/u', ' ', trim((string) $value)) ?: '', $limit, '...');
    }

    private function wrappedText(string $value): string
    {
        $text = $this->singleLine($value, 150);
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line.' '.$word);

            if (mb_strlen($candidate) > 34 && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }

            if (count($lines) === 3) {
                break;
            }
        }

        if ($line !== '' && count($lines) < 4) {
            $lines[] = $line;
        }

        return implode("\n", array_slice($lines, 0, 4));
    }

    private function filterPath(string $path): string
    {
        return str_replace(['\\', ':', "'"], ['\\\\', '\\:', "\\'"], $path);
    }
}
