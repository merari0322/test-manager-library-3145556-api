<?php

namespace App\Library\Application\Ports;

/** D-08: cover binaries live outside the database; the database keeps only an opaque key. */
interface CoverStorage
{
    public function put(string $key, string $bytes): void;

    public function get(string $key): ?string;

    public function delete(string $key): void;
}
