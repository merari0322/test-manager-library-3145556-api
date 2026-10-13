<?php

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Errors\BookNotAvailable;
use App\Library\Domain\Errors\ItemAlreadyReturned;
use App\Library\Domain\Loan;
use App\Library\Domain\LoanItem;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DbLoanRepository implements LoanRepository
{
    public function get(string $id): ?Loan
    {
        $row = DB::table('loan')->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        $items = DB::table('loan_item')->where('loan_id', $id)->orderBy('book_title')->get()
            ->map(fn ($i) => new LoanItem($i->id, $i->book_id, $i->book_title, $i->isbn, $i->category_name,
                new DateTimeImmutable($i->due_at), $i->returned_at === null ? null : new DateTimeImmutable($i->returned_at)))
            ->all();

        return Loan::restore($row->id, $row->member_id, $row->loaned_by_user_id, new DateTimeImmutable($row->loaned_at), $items);
    }

    public function add(Loan $loan, array $books): void
    {
        DB::transaction(function () use ($loan, $books) {
            // ADR-002 / RNF-2: the conditional update only succeeds while a copy is left;
            // ck_book_available_range is the last barrier.
            foreach ($books as $book) {
                $taken = DB::table('book')->where('id', $book->id)->whereNull('deleted_at')
                    ->where('available_copies', '>', 0)->decrement('available_copies');
                if ($taken === 0) {
                    throw new BookNotAvailable("No quedan ejemplares disponibles de «{$book->title}».");
                }
            }

            DB::table('loan')->insert([
                'id' => $loan->id,
                'member_id' => $loan->memberId,
                'loaned_by_user_id' => $loan->loanedByUserId,
                'loaned_at' => $loan->loanedAt->format(DATE_ATOM),
            ]);
            foreach ($loan->items as $item) {
                DB::table('loan_item')->insert([
                    'id' => $item->id,
                    'loan_id' => $loan->id,
                    'book_id' => $item->bookId,
                    'book_title' => $item->bookTitle,
                    'isbn' => $item->isbn,
                    'category_name' => $item->categoryName,
                    'due_at' => $item->dueAt->format(DATE_ATOM),
                    'returned_at' => null,
                ]);
            }
        });
    }

    public function saveReturn(Loan $loan, LoanItem $item): void
    {
        DB::transaction(function () use ($loan, $item) {
            // CA-07.2: never adds twice, even when two returns race.
            $updated = DB::table('loan_item')->where('id', $item->id)->where('loan_id', $loan->id)
                ->whereNull('returned_at')->update(['returned_at' => $item->returnedAt->format(DATE_ATOM)]);
            if ($updated === 0) {
                throw new ItemAlreadyReturned('Esta línea ya fue devuelta.');
            }

            DB::table('book')->where('id', $item->bookId)->increment('available_copies');
        });
    }

    public function overdue(DateTimeImmutable $now): array
    {
        return DB::table('loan_item as i')
            ->join('loan as l', 'l.id', '=', 'i.loan_id')
            ->join('member as m', 'm.id', '=', 'l.member_id')
            ->whereNull('i.returned_at')
            ->where('i.due_at', '<', $now->format(DATE_ATOM))
            ->orderBy('i.due_at')
            ->get(['i.loan_id', 'i.id as item_id', 'i.book_title', 'm.full_name as member_name', 'i.due_at'])
            ->map(fn ($r) => [
                'loan_id' => $r->loan_id,
                'item_id' => $r->item_id,
                'book_title' => $r->book_title,
                'member_name' => $r->member_name,
                'due_at' => (new DateTimeImmutable($r->due_at))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                'days_late' => (int) (new DateTimeImmutable($r->due_at))->diff($now)->days,
            ])->all();
    }
}
