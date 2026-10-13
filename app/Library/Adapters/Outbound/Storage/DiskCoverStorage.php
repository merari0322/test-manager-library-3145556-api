<?php

namespace App\Library\Adapters\Outbound\Storage;

use App\Library\Application\Ports\CoverStorage;

/** D-08: local disk under COVERS_DIR (S3 would be another adapter, article IV OCP). */
final class DiskCoverStorage implements CoverStorage
{
    public function __construct(private readonly string $dir)
    {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    public function put(string $key, string $bytes): void
    {
        file_put_contents($this->path($key), $bytes);
    }

    public function get(string $key): ?string
    {
        $path = $this->path($key);

        return is_file($path) ? file_get_contents($path) : null;
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(string $key): string
    {
        return $this->dir.DIRECTORY_SEPARATOR.basename($key);
    }
}
