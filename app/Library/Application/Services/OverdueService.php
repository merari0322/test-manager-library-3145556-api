<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;

/** HU-09: overdue lines. */
final class OverdueService
{
    public function __construct(private readonly LoanRepository $loans, private readonly Clock $clock)
    {
    }

    public function overdue(): array
    {
        return $this->loans->overdue($this->clock->now());
    }
}
