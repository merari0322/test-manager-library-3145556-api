<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\LoanItem;

/** HU-07: return one loan line. */
final class ReturnItemService
{
    public function __construct(private readonly LoanRepository $loans, private readonly Clock $clock)
    {
    }

    public function return(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->get($loanId) ?? throw new NotFound('El préstamo no existe.');
        $item = $loan->returnItem($itemId, $this->clock->now());
        $this->loans->saveReturn($loan, $item);

        return $item;
    }
}
