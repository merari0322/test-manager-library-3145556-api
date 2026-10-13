<?php

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\ReportReader;
use App\Library\Domain\DateRange;
use Illuminate\Support\Facades\DB;

/** ADR-004: computed in the engine, grouped by frozen values, never joined to the live catalog. No members nor librarians. */
final class DbReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        return DB::table('loan_item as i')
            ->join('loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.loaned_at', '>=', $range->from->format(DATE_ATOM))
            ->where('l.loaned_at', '<', $range->endExclusive()->format(DATE_ATOM))
            ->groupBy('i.book_id', 'i.book_title', 'i.category_name')
            ->orderByDesc('times_borrowed')->orderBy('i.book_title')
            ->get(['i.book_id', 'i.book_title', 'i.category_name', DB::raw('count(*) as times_borrowed')])
            ->map(fn ($r) => [
                'book_id' => $r->book_id,
                'book_title' => $r->book_title,
                'category_name' => $r->category_name,
                'times_borrowed' => (int) $r->times_borrowed,
            ])->all();
    }
}
