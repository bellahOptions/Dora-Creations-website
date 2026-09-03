<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PlaceholderImage
{
    /**
     * Branded stand-in for missing/demo photography: the Dora Creations
     * mark centered on a brand color, so an unfinished product/category/
     * slide never shows as a bare block of text or a broken-image icon.
     * Ink/cream is chosen automatically so the mark stays legible on
     * whatever background color is passed in.
     */
    public static function svg(string $label, string $background = '#FFEEC6', int $width = 900, int $height = 1125): string
    {
        $ink = static::inkFor($background);
        $mark = static::markGroup($width, $height, $ink, offsetRatio: -0.05);

        $label = htmlspecialchars(mb_strtoupper($label), ENT_QUOTES | ENT_XML1);
        $fontSize = max(13, (int) round($width / 32));
        $labelY = $height * 0.6;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="{$background}" />
    {$mark}
    <text x="50%" y="{$labelY}" fill="{$ink}" font-family="Arial, sans-serif" font-size="{$fontSize}"
          font-weight="600" letter-spacing="2" text-anchor="middle" opacity="0.55">{$label}</text>
</svg>
SVG;
    }

    public static function store(string $disk, string $path, string $label, string $background = '#FFEEC6'): string
    {
        Storage::disk($disk)->put($path, static::svg($label, $background));

        return $path;
    }

    /**
     * A text-free placeholder for spots that already overlay their own
     * heading as HTML (hero slides) — baking the label into the image too
     * would duplicate it visually once the image is stretched/cropped.
     */
    public static function plain(string $background = '#FFEEC6', int $width = 1600, int $height = 900): string
    {
        $mark = static::markGroup($width, $height, static::inkFor($background));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="{$background}" />
    {$mark}
</svg>
SVG;
    }

    public static function storePlain(string $disk, string $path, string $background = '#FFEEC6'): string
    {
        Storage::disk($disk)->put($path, static::plain($background));

        return $path;
    }

    /**
     * Centers the Dora Creations wordmark (read live from the public logo
     * asset, so both stay in sync) inside a canvas of arbitrary size.
     */
    private static function markGroup(int $width, int $height, string $ink, float $offsetRatio = 0): string
    {
        [$markSourceWidth, $markSourceHeight, $paths] = static::markPaths();

        $markWidth = $width * 0.46;
        $scale = $markWidth / $markSourceWidth;
        $markHeight = $markSourceHeight * $scale;

        $x = ($width - $markWidth) / 2;
        $y = ($height - $markHeight) / 2 + ($height * $offsetRatio);

        return sprintf(
            '<g transform="translate(%.2f, %.2f) scale(%.4f)" fill="%s" opacity="0.9">%s</g>',
            $x,
            $y,
            $scale,
            $ink,
            $paths
        );
    }

    /**
     * @return array{0: float, 1: float, 2: string}
     */
    private static function markPaths(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $source = file_get_contents(public_path('black-logo.svg')) ?: '';

        $viewBox = [254.47, 86.09];
        if (preg_match('/viewBox="0 0 ([\d.]+) ([\d.]+)"/', $source, $vb)) {
            $viewBox = [(float) $vb[1], (float) $vb[2]];
        }

        $paths = '';
        if (preg_match_all('/<path[^>]*\/>|<path[^>]*>.*?<\/path>/s', $source, $matches)) {
            $paths = implode('', $matches[0]);
        }

        return $cached = [$viewBox[0], $viewBox[1], $paths];
    }

    /**
     * Picks dark ink or brand cream for the mark/label, whichever reads
     * legibly against the given background color.
     */
    private static function inkFor(string $background): string
    {
        $hex = ltrim($background, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return '#15130F';
        }

        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.55 ? '#15130F' : '#FFEEC6';
    }
}
