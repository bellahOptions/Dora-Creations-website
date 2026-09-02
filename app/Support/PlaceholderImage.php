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
}
