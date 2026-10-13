<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Loan;
use DateTimeImmutable;

/** HU-08 (view one loan) and HU-09 (overdue lines). */
final class OverdueService
{
    public function __construct(private readonly LoanRepository $loans, private readonly Clock $clock)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function get(string $loanId): Loan
    {
        return $this->loans->get($loanId) ?? throw new NotFound('El préstamo no existe.');
    }

    public function overdue(): array
    {
        return $this->loans->overdue($this->clock->now());
    }
}
