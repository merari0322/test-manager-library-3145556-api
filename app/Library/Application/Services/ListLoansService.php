<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\DateRange;

/** HU-08: list loans by date range, status and member (CA-08.2). */
final class ListLoansService
{
    public function __construct(private readonly LoanRepository $loans, private readonly Clock $clock)
    {
    }

    public function list(?string $from, ?string $to, ?string $status, ?string $memberId, int $page, int $size): array
    {
        $range = $from !== null && $to !== null ? DateRange::of($from, $to) : null;

        return $this->loans->search($range, $status, $memberId, $this->clock->now(), $page, $size)
            + ['page' => $page, 'size' => $size];
    }
}
