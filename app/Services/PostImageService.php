<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostImageService
{
    public function store(Post $post, UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $dimensions = $bytes === false ? false : @getimagesizefromstring($bytes);
        if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1
            || $dimensions[0] * $dimensions[1] > 40_000_000) {
            throw ValidationException::withMessages(['image' => ['La foto non è leggibile o è troppo grande.']]);
        }
        $source = $bytes === false ? false : @imagecreatefromstring($bytes);
        if ($source === false) {
            throw ValidationException::withMessages(['image' => ['La foto non è leggibile.']]);
        }
        if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
            $degrees = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            if ($degrees !== 0) {
                $rotated = imagerotate($source, $degrees, 0);
                if ($rotated !== false) {
                    imagedestroy($source);
                    $source = $rotated;
                }
            }
        }
        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1 || $width * $height > 40_000_000) {
            imagedestroy($source);
            throw ValidationException::withMessages(['image' => ['La foto è troppo grande.']]);
        }
        $ratio = min(1, 2048 / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        ob_start();
        imagejpeg($target, null, 82);
        $encoded = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);
        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages(['image' => ['La foto non è stata elaborata.']]);
        }
        $disk = 'public';
        $path = 'post-images/'.Str::uuid().'.jpg';
        Storage::disk($disk)->put($path, $encoded);
        return [
            'image_disk' => $disk,
            'image_path' => $path,
            'image_url' => Storage::disk($disk)->url($path),
            'image_mime' => 'image/jpeg',
            'image_size_bytes' => strlen($encoded),
        ];
    }

    public function deleteForPost(Post $post): void
    {
        if ($post->image_disk && $post->image_path) Storage::disk($post->image_disk)->delete($post->image_path);
    }

    public function emptyPayload(): array
    {
        return [
            'image_disk' => null, 'image_path' => null, 'image_url' => null,
            'image_mime' => null, 'image_size_bytes' => null,
        ];
    }
}
