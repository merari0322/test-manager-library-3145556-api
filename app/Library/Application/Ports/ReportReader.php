<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\DateRange;

/** ADR-004: one aggregate query grouped by the frozen values. */
interface ReportReader
{
    /** @return list<array{book_id: string, book_title: string, category_name: string, times_borrowed: int}> */
    public function mostBorrowed(DateRange $range): array;
}
