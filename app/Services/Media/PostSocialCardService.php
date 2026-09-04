<?php

namespace App\Services\Media;

use App\Models\Post;
use App\Support\PostCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostSocialCardService
{
    public function __construct(private readonly PublicPostMediaContent $publicContent) {}

    public function urlFor(Post $post): string
    {
        $fallback = asset('images/share/spoton-share.png');

        if (! function_exists('imagecreatetruecolor')) {
            return $fallback;
        }

        $font = (string) config('spoton.share_video.font_path');
        $fontBold = (string) config('spoton.share_video.font_bold_path');

        if (! is_file($font) || ! is_file($fontBold)) {
            return $fallback;
        }

        $disk = (string) config('spoton.social_card.disk', 'public');
        $directory = trim((string) config('spoton.social_card.directory', 'share-cards'), '/');
        $version = (string) config('spoton.share_video.template_version', 'v1');
        $stamp = $post->updated_at?->getTimestamp() ?: time();
        $path = "{$directory}/{$post->id}-{$version}-{$stamp}.png";

        if (! Storage::disk($disk)->exists($path)) {
            try {
                $contents = $this->render($post, $font, $fontBold);

                if (! Storage::disk($disk)->put($path, $contents)) {
                    return $fallback;
                }
            } catch (\Throwable) {
                return $fallback;
            }
        }

        return Storage::disk($disk)->url($path);
    }

    public function invalidate(Post $post): void
    {
        $disk = (string) config('spoton.social_card.disk', 'public');
        $directory = trim((string) config('spoton.social_card.directory', 'share-cards'), '/');

        foreach (Storage::disk($disk)->files($directory) as $path) {
            if (str_starts_with(basename($path), $post->id.'-')) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function render(Post $post, string $font, string $fontBold): string
    {
        $post->loadMissing(['author', 'location']);
        $image = imagecreatetruecolor(1200, 630);
        $ink = imagecolorallocate($image, 17, 24, 39);
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 209, 213, 219);
        $yellow = imagecolorallocate($image, 255, 212, 77);
        $cyan = imagecolorallocate($image, 56, 207, 208);
        $pink = imagecolorallocate($image, 238, 91, 143);
        imagefilledrectangle($image, 0, 0, 1200, 630, $ink);
        imagefilledrectangle($image, 0, 0, 18, 630, $pink);
        imagefilledrectangle($image, 18, 0, 28, 630, $cyan);

        imagettftext($image, 34, 0, 70, 78, $white, $fontBold, 'SpotOn');
        imagettftext($image, 18, 0, 70, 122, $yellow, $fontBold, PostCategory::label($post->category));

        $author = Str::limit($this->publicContent->author($post), 40);
        imagettftext($image, 28, 0, 70, 190, $white, $fontBold, $author);

        $lines = $this->wrap($this->publicContent->text($post), $fontBold, 31, 820, 3);
        $y = 260;

        foreach ($lines as $line) {
            imagettftext($image, 31, 0, 70, $y, $white, $fontBold, $line);
            $y += 55;
        }

        $location = Str::limit($post->location->name.', '.$post->location->city, 70);
        imagettftext($image, 20, 0, 70, 555, $muted, $font, $location);

        imagefilledellipse($image, 1020, 315, 150, 150, $yellow);
        imagefilledpolygon($image, [1000, 270, 1000, 360, 1070, 315], 3, $ink);
        imagettftext($image, 16, 0, 914, 430, $muted, $font, $post->audio_path ? 'ASCOLTA' : 'SCOPRI');

        ob_start();
        imagepng($image, null, 8);
        $contents = ob_get_clean();
        imagedestroy($image);

        if (! is_string($contents) || $contents === '') {
            throw new \RuntimeException('Card social non generata.');
        }

        return $contents;
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $value, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $text = Str::limit(preg_replace('/\s+/u', ' ', trim($value)) ?: '', 155, '...');
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line.' '.$word);
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = $box ? abs($box[2] - $box[0]) : 0;

            if ($width > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }

            if (count($lines) === $maxLines) {
                break;
            }
        }

        if ($line !== '' && count($lines) < $maxLines) {
            $lines[] = $line;
        }

        return $lines;
    }
}
