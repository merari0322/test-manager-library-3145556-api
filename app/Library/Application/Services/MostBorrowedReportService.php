<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\ReportReader;
use App\Library\Domain\DateRange;

/** HU-10: most borrowed books in a date range. */
final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reader)
    {
    }

    public function report(string $from, string $to): array
    {
        return ['from' => $from, 'to' => $to, 'rows' => $this->reader->mostBorrowed(DateRange::of($from, $to))];
    }
}
