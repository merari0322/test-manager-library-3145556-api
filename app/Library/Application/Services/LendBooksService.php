<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Errors\MemberHasOverdue;
use App\Library\Domain\Errors\MemberLimitExceeded;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Loan;
use App\Library\Domain\Uuid;

/** HU-06: register a loan (flow of architecture.md section 4). */
final class LendBooksService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly BookRepository $books,
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {
    }

    /** @param list<string> $bookIds */
    public function lend(string $memberId, string $userId, array $bookIds): Loan
    {
        $now = $this->clock->now();
        if (! $this->members->exists($memberId)) {
            throw new NotFound('El socio no existe.');
        }

        // CA-06.5: a member with an overdue book cannot borrow.
        if ($this->members->hasOverdue($memberId, $now)) {
            throw new MemberHasOverdue('El socio tiene libros vencidos y no puede pedir otro.');
        }

        // CA-06.4: at most 3 unreturned books in total.
        $current = $this->members->unreturnedCount($memberId);
        if ($current + count($bookIds) > Loan::MAX_ITEMS) {
            throw new MemberLimitExceeded("El socio ya tiene {$current} libros sin devolver; el máximo es 3.", [
                ['field' => 'items', 'issue' => "sin devolver: {$current}"],
            ]);
        }

        $loan = Loan::open(Uuid::v4(), $memberId, $userId, $now);
        $books = [];
        foreach ($bookIds as $bookId) {
            $book = $this->books->get($bookId) ?? throw new NotFound('El libro no existe.');
            $loan->addItem($book, $this->books->categoryName($book->categoryId));
            $books[] = $book;
        }
        $loan->assertNotEmpty();

        $this->loans->add($loan, $books);

        return $loan;
    }
}
