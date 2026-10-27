<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Phone photos of stock arrive at 4000px and several MB. With GD (standard on
 * Hostinger) each upload is stored as a 1600px WebP for the gallery and an 800px
 * WebP for cards; without GD the original is kept and used for both.
 */
final class PhotoResizer
{
    public const int MAIN = 1600;

    public const int THUMB = 800;

    /** @return array{path: string, thumb_path: string|null, width: int|null, height: int|null} */
    public static function store(UploadedFile $file, string $dir, string $disk = 'uploads'): array
    {
        $size = @getimagesize($file->getRealPath()) ?: [null, null];
        $image = self::open($file->getRealPath(), (string) $file->getMimeType());

        if (! $image || ! function_exists('imagewebp')) {
            return ['path' => $file->store($dir, $disk), 'thumb_path' => null, 'width' => $size[0], 'height' => $size[1]];
        }

        $base = $dir.'/'.Str::random(32);
        [$path, $w, $h] = self::write($image, $base.'.webp', self::MAIN, $disk);
        [$thumb] = self::write($image, $base.'-thumb.webp', self::THUMB, $disk);
        imagedestroy($image);

        return ['path' => $path, 'thumb_path' => $thumb, 'width' => $w, 'height' => $h];
    }

    private static function open(string $path, string $mime): ?\GdImage
    {
        if (! extension_loaded('gd')) {
            return null;
        }
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        if (! $img) {
            return null;
        }
        // Phones store portrait shots sideways with an EXIF flag; turn them upright.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
            $rotated = match ($o) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => null,
            };
            if ($rotated) {
                imagedestroy($img);
                $img = $rotated;
            }
        }

        return $img;
    }

    /** @return array{0: string, 1: int, 2: int} */
    private static function write(\GdImage $src, string $path, int $maxWidth, string $disk): array
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $out = $src;
        if ($w > $maxWidth) {
            $nh = (int) round($h * $maxWidth / $w);
            $out = imagescale($src, $maxWidth, $nh, IMG_BICUBIC) ?: $src;
            [$w, $h] = [$maxWidth, $nh];
        }
        ob_start();
        imagewebp($out, null, 80);
        Storage::disk($disk)->put($path, (string) ob_get_clean());
        if ($out !== $src) {
            imagedestroy($out);
        }

        return [$path, $w, $h];
    }
}
