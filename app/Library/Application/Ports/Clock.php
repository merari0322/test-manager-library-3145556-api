<?php

namespace App\Library\Application\Ports;

use DateTimeImmutable;

/** D-05: time is a port (UTC instants). */
interface Clock
{
    public function now(): DateTimeImmutable;
}
