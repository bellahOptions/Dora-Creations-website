<?php

namespace App\Support\Cloudinary;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Api\NotFound;
use Cloudinary\Uploader;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Throwable;

/**
 * A minimal Flysystem adapter over Cloudinary's legacy (v1) PHP SDK — used
 * instead of the official cloudinary-labs/cloudinary-laravel package, which
 * doesn't yet support Laravel 13, and instead of cloudinary/cloudinary_php
 * v3+, which pins to Guzzle ^7 and conflicts with this app's Guzzle 8.
 *
 * Every image on the site (product photos, category/slide art, review
 * screenshots, user avatars) is a plain "image" resource, so resource_type
 * is hardcoded rather than tracked per file. The path given by Flysystem
 * callers is used as the Cloudinary public_id verbatim (extension and all),
 * which keeps read/write/delete/url all trivially reversible without a
 * separate lookup table.
 */
class CloudinaryAdapter implements FilesystemAdapter
{
    private const RESOURCE_TYPE = 'image';

    public function __construct(private array $config)
    {
        Cloudinary::config([
            'cloud_name' => $config['cloud_name'],
            'api_key' => $config['api_key'],
            'api_secret' => $config['api_secret'],
            'secure' => true,
        ]);
    }

    private function publicId(string $path): string
    {
        $folder = trim($this->config['folder'] ?? '', '/');

        return $folder !== '' ? $folder.'/'.ltrim($path, '/') : ltrim($path, '/');
    }

    public function fileExists(string $path): bool
    {
        try {
            (new Api)->resource($this->publicId($path), ['resource_type' => self::RESOURCE_TYPE]);

            return true;
        } catch (NotFound) {
            return false;
        } catch (Throwable) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $tmp = tmpfile();

        if ($tmp === false) {
            throw UnableToWriteFile::atLocation($path, 'Could not open a temporary file for upload.');
        }

        try {
            fwrite($tmp, $contents);
            $tmpPath = stream_get_meta_data($tmp)['uri'];

            Uploader::upload($tmpPath, [
                'public_id' => $this->publicId($path),
                'resource_type' => self::RESOURCE_TYPE,
                'overwrite' => true,
                'invalidate' => true,
                'unique_filename' => false,
                'use_filename' => false,
            ]);
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        } finally {
            fclose($tmp);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents($this->getUrl($path));

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $stream = @fopen($this->getUrl($path), 'rb');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            Uploader::destroy($this->publicId($path), [
                'resource_type' => self::RESOURCE_TYPE,
                'invalidate' => true,
            ]);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        // Cloudinary has no real directory concept — folder-prefixed public_ids
        // disappear on their own once the last asset under them is deleted.
    }

    public function createDirectory(string $path, Config $config): void
    {
        // No-op — folders are implicit in Cloudinary, created on first upload.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Cloudinary assets are served from a public CDN URL by design;
        // there's no private/public ACL to toggle for the "upload" type we use.
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PUBLIC);
    }

    public function mimeType(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::mimeType($path, 'Not supported by the Cloudinary adapter.');
    }

    public function lastModified(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::lastModified($path, 'Not supported by the Cloudinary adapter.');
    }

    public function fileSize(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::fileSize($path, 'Not supported by the Cloudinary adapter.');
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            Uploader::rename($this->publicId($source), $this->publicId($destination), [
                'resource_type' => self::RESOURCE_TYPE,
                'overwrite' => true,
            ]);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->write($destination, $this->read($source), $config);
    }

    /**
     * Called directly by Illuminate\Filesystem\FilesystemAdapter::url() —
     * it checks method_exists($adapter, 'getUrl') before falling back to
     * any other URL strategy, so this is all that's needed for
     * Storage::disk('cloudinary')->url($path) to work.
     */
    public function getUrl(string $path): string
    {
        return cloudinary_url($this->publicId($path), [
            'secure' => true,
            'resource_type' => self::RESOURCE_TYPE,
        ]);
    }
}
