<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PlaceholderImage
{
    /**
     * Build a self-contained SVG placeholder so demo content never depends on
     * an external image host. Swap real product photography in later via
     * the admin Products CRUD without touching this helper.
     */
    public static function svg(string $label, string $background = '#1B1913', string $foreground = '#F8F4EC', int $width = 900, int $height = 1125): string
    {
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_XML1);
        $fontSize = (int) round($width / 14);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="{$background}" />
    <text x="50%" y="50%" fill="{$foreground}" font-family="Arial, sans-serif" font-size="{$fontSize}"
          font-weight="700" text-anchor="middle" dominant-baseline="middle" opacity="0.9">{$label}</text>
</svg>
SVG;
    }

    public static function store(string $disk, string $path, string $label, string $background = '#1B1913', string $foreground = '#F8F4EC'): string
    {
        Storage::disk($disk)->put($path, static::svg($label, $background, $foreground));

        return $path;
    }

    /**
     * A text-free placeholder for spots that already overlay their own
     * heading as HTML (hero slides) — baking the label into the image too
     * would duplicate it visually once the image is stretched/cropped.
     */
    public static function plain(string $background = '#1B1913', string $accent = '#F8F4EC', int $width = 1600, int $height = 900): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="{$background}" />
    <circle cx="{$width}" cy="0" r="{$width}" fill="{$accent}" opacity="0.06" />
    <circle cx="0" cy="{$height}" r="{$width}" fill="{$accent}" opacity="0.06" />
</svg>
SVG;
    }

    public static function storePlain(string $disk, string $path, string $background = '#1B1913', string $accent = '#F8F4EC'): string
    {
        Storage::disk($disk)->put($path, static::plain($background, $accent));

        return $path;
    }
}
