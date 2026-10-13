<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Loan;
use DateTimeImmutable;

/** HU-08: view one loan with its computed status (CA-08.1). */
final class GetLoanService
{
    public function __construct(private readonly LoanRepository $loans, private readonly Clock $clock)
    {
    }

    public function get(string $loanId): Loan
    {
        return $this->loans->get($loanId) ?? throw new NotFound('El préstamo no existe.');
    }

    /** The instant used to compute the status. */
    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }
}
