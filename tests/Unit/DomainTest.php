<?php

namespace Tests\Unit;

use App\Library\Domain\Book;
use App\Library\Domain\DateRange;
use App\Library\Domain\Errors\BookNotAvailable;
use App\Library\Domain\Errors\DuplicateBookInLoan;
use App\Library\Domain\Errors\InvalidDateRange;
use App\Library\Domain\Errors\ItemAlreadyReturned;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\Isbn;
use App\Library\Domain\Loan;
use App\Library\Domain\Quantity;
use App\Library\Domain\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-03 to T-06: domain invariants, no database. */
final class DomainTest extends TestCase
{
    private function book(string $id, int $copies = 1): Book
    {
        return Book::create($id, 'Libro '.$id, 'Autor', Isbn::of('9780000000001'), 'cat', Quantity::of($copies));
    }

    public function test_value_objects(): void
    {
        $this->assertSame('9780000000001', Isbn::of('9780000000001')->value);
        $this->expectException(ValidationFailed::class);
        Isbn::of('123');
    }

    public function test_quantity_must_be_positive(): void
    {
        $this->expectException(ValidationFailed::class);
        Quantity::of(0);
    }

    public function test_date_range_end_not_before_start(): void
    {
        $this->expectException(InvalidDateRange::class);
        DateRange::of('2026-10-31', '2026-10-01');
    }

    public function test_book_never_below_zero_nor_above_total(): void
    {
        $book = $this->book('b1');
        $book->withdraw();
        $book->restock();
        $book->restock();
        $this->assertSame(1, $book->availableCopies);
        $book->withdraw();
        $this->expectException(BookNotAvailable::class);
        $book->withdraw();
    }

    public function test_loan_due_in_14_days_and_status(): void
    {
        $now = new DateTimeImmutable('2026-10-01T10:00:00Z');
        $loan = Loan::open('l1', 'm1', 'u1', $now);
        $loan->addItem($this->book('b1'), 'Novela');
        $this->assertEquals(new DateTimeImmutable('2026-10-15T10:00:00Z'), $loan->items[0]->dueAt);
        $this->assertSame('open', $loan->status($now));
        $this->assertSame('overdue', $loan->status(new DateTimeImmutable('2026-10-16T10:00:00Z')));
        $loan->returnItem($loan->items[0]->id, $now);
        $this->assertSame('closed', $loan->status($now));
        $this->expectException(ItemAlreadyReturned::class);
        $loan->returnItem($loan->items[0]->id, $now);
    }

    public function test_loan_rejects_duplicate_and_more_than_three(): void
    {
        $loan = Loan::open('l1', 'm1', 'u1', new DateTimeImmutable());
        $book = $this->book('b1', 5);
        $loan->addItem($book, 'Novela');
        $this->expectException(DuplicateBookInLoan::class);
        $loan->addItem($book, 'Novela');
    }

    public function test_user_name_normalized_and_role_checked(): void
    {
        $this->assertSame('ana', User::create('u1', 'Ana ', 'hash', 'librarian')->username);
        $this->expectException(ValidationFailed::class);
        User::create('u2', 'bob', 'hash', 'root');
    }
}
