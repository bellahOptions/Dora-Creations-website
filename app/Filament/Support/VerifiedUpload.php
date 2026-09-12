<?php

namespace App\Filament\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Wraps a FileUpload field's default save behavior with a reachability
 * check against the disk's public URL. Cloudinary's API can accept an
 * upload (no exception thrown) yet leave the asset unreachable — this
 * catches that case, deletes the orphaned upload, and halts the save
 * (rolling back the whole record) instead of persisting a product,
 * category, slide, etc. with a broken image reference.
 */
class VerifiedUpload
{
    public static function apply(BaseFileUpload $upload): BaseFileUpload
    {
        return $upload->saveUploadedFileUsing(static function (BaseFileUpload $component, TemporaryUploadedFile $file) {
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

                Notification::make()
                    ->danger()
                    ->title('Image upload failed')
                    ->body('"'.$file->getClientOriginalName().'" was uploaded but could not be verified on Cloudinary. Please try again.')
                    ->send();

                throw (new Halt)->rollBackDatabaseTransaction();
            }

            return $path;
        });
    }

    private static function isReachable(string $url): bool
    {
        try {
            return Http::timeout(10)->head($url)->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
