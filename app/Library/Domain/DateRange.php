<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\InvalidDateRange;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/** Value object: end >= start (T-03, CA-10.2). Dates are whole UTC days, both inclusive. */
final class DateRange
{
    private function __construct(public readonly DateTimeImmutable $from, public readonly DateTimeImmutable $to)
    {
    }

    public static function of(string $from, string $to): self
    {
        $utc = new DateTimeZone('UTC');
        $start = new DateTimeImmutable($from.' 00:00:00', $utc);
        $end = new DateTimeImmutable($to.' 00:00:00', $utc);
        if ($end < $start) {
            throw new InvalidDateRange('La fecha final es anterior a la inicial.');
        }

        return new self($start, $end);
    }

    /** First instant after the inclusive end day (see ADR-005, INC-08). */
    public function endExclusive(): DateTimeImmutable
    {
        return $this->to->add(new DateInterval('P1D'));
    }
}
