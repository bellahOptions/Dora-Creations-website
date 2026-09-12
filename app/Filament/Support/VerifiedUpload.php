<?php

namespace App\Filament\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Wraps a FileUpload field with fixes on top of Filament's defaults:
 *
 * 1. Verifies a Cloudinary upload actually resolved to a reachable URL
 *    before saving — Cloudinary's API can accept an upload (no exception
 *    thrown) yet leave the asset unreachable. If that happens, the orphaned
 *    upload is deleted and the save is halted instead of persisting a
 *    product, category, slide, etc. with a broken image reference.
 * 2. Stops already-stored-but-broken references from ever reaching the
 *    browser. Filament's default file-info lookup was disabled project-wide
 *    (it called Cloudinary's admin API on every form load, which was slow
 *    and rate-limited), but that lookup was also what filtered out missing
 *    files — without it, a broken path gets handed straight to the FilePond
 *    widget, which fetches it to build a preview and hangs forever with no
 *    error and no way to remove it. This does a cheap, cached HEAD request
 *    against the CDN URL instead of the admin API, and simply omits the
 *    file from the widget's state when it's unreachable, leaving an empty
 *    (not stuck) upload slot the admin can fill in again.
 * 3. Actually deletes the file from disk (Cloudinary included) when an
 *    admin removes it from the widget — Filament does not do this by
 *    default; removing a file only detaches it from the form's state.
 */
class VerifiedUpload
{
    public static function apply(BaseFileUpload $upload): BaseFileUpload
    {
        $upload
            ->saveUploadedFileUsing(static function (BaseFileUpload $component, TemporaryUploadedFile $file) {
                try {
                    if (! $file->exists()) {
                        return null;
                    }
                } catch (Throwable) {
                    return null;
                }

                $disk = $component->getDisk();

                $path = $file->storePubliclyAs(
                    $component->getDirectory(),
                    $component->getUploadedFileNameForStorage($file),
                    $component->getDiskName(),
                );

                if ($component->getDiskName() === 'cloudinary' && ! static::isReachable($disk->url($path))) {
                    try {
                        $disk->delete($path);
                    } catch (Throwable) {
                        // Best-effort cleanup — the halt below is what matters.
                    }

                    static::forgetReachability($path);

                    Notification::make()
                        ->danger()
                        ->title('Image upload failed')
                        ->body('"'.$file->getClientOriginalName().'" was uploaded but could not be verified on Cloudinary. Please try again.')
                        ->send();

                    throw (new Halt)->rollBackDatabaseTransaction();
                }

                return $path;
            })
            ->deleteUploadedFileUsing(static function (BaseFileUpload $component, string $file) {
                try {
                    $component->getDisk()->delete($file);
                } catch (Throwable) {
                    // Best-effort — the reference is being removed from the record either way.
                }

                static::forgetReachability($file);
            });

        if (! $upload->shouldFetchFileInformation()) {
            $upload->getUploadedFileUsing(static function (BaseFileUpload $component, string $file, string | array | null $storedFileNames): ?array {
                $disk = $component->getDisk();
                $url = $disk->url($file);

                if ($component->getDiskName() === 'cloudinary' && ! static::isCachedReachable($file, $url)) {
                    return null;
                }

                return [
                    'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                    'size' => 0,
                    'type' => null,
                    'url' => $url,
                ];
            });
        }

        return $upload;
    }

    private static function isCachedReachable(string $file, string $url): bool
    {
        return Cache::remember(
            static::reachabilityCacheKey($file),
            now()->addMinutes(30),
            fn () => static::isDisplayable($url),
        );
    }

    private static function forgetReachability(string $file): void
    {
        Cache::forget(static::reachabilityCacheKey($file));
    }

    private static function reachabilityCacheKey(string $file): string
    {
        return 'verified-upload:reachable:'.md5($file);
    }

    /**
     * Used at save time to actively verify a just-uploaded file, where a
     * failure to connect is itself meaningful (the upload likely didn't
     * really land) — so any exception here is treated as "not reachable".
     */
    private static function isReachable(string $url): bool
    {
        try {
            return Http::timeout(10)->head($url)->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Used when deciding whether to keep showing an already-stored file.
     * A confirmed 4xx/5xx response means the asset is genuinely gone, so
     * we hide it. A network error or timeout doesn't tell us that — it
     * just means we couldn't check right now — so it fails open and the
     * file is still shown, rather than making a good image vanish because
     * of a transient blip talking to Cloudinary.
     */
    private static function isDisplayable(string $url): bool
    {
        try {
            return Http::timeout(10)->head($url)->successful();
        } catch (Throwable) {
            return true;
        }
    }
}
