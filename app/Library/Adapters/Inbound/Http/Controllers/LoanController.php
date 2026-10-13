<?php

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Application\Services\LendBooksService;
use App\Library\Application\Services\ListLoansService;
use App\Library\Application\Services\MostBorrowedReportService;
use App\Library\Application\Services\OverdueService;
use App\Library\Application\Services\ReturnItemService;
use App\Library\Domain\Loan;
use App\Library\Domain\LoanItem;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoanController
{
    public function store(Request $request, LendBooksService $lend, OverdueService $query): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array'],
            'items.*.book_id' => ['required', 'uuid'],
        ]);

        $loan = $lend->lend($data['member_id'], $request->attributes->get('user_id'),
            array_map(fn ($i) => $i['book_id'], $data['items']));

        return response()->json($this->loan($loan, $query->now()), 201);
    }

    public function index(Request $request, ListLoansService $list, OverdueService $query): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from'],
            'status' => ['nullable', 'in:open,overdue,closed'],
            'member_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $list->list($data['from'] ?? null, $data['to'] ?? null, $data['status'] ?? null,
            $data['member_id'] ?? null, (int) ($data['page'] ?? 1), (int) ($data['size'] ?? 20));
        $now = $query->now();
        $result['items'] = array_map(fn (Loan $l) => $this->loan($l, $now), $result['items']);

        return response()->json($result);
    }

    public function report(Request $request, MostBorrowedReportService $service): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($service->report($data['from'], $data['to']));
    }

    public function show(string $id, OverdueService $query): JsonResponse
    {
        return response()->json($this->loan($query->get($id), $query->now()));
    }

    public function return(string $loanId, string $itemId, ReturnItemService $service): JsonResponse
    {
        return response()->json($this->item($service->return($loanId, $itemId)));
    }

    public function overdue(OverdueService $query): JsonResponse
    {
        return response()->json(['items' => $query->overdue()]);
    }

    private function loan(Loan $loan, DateTimeImmutable $now): array
    {
        return [
            'id' => $loan->id,
            'member_id' => $loan->memberId,
            'loaned_at' => self::utc($loan->loanedAt),
            'status' => $loan->status($now),
            'items' => array_map(fn (LoanItem $i) => $this->item($i), $loan->items),
        ];
    }

    private function item(LoanItem $i): array
    {
        return [
            'id' => $i->id,
            'book_id' => $i->bookId,
            'book_title' => $i->bookTitle,
            'isbn' => $i->isbn,
            'category_name' => $i->categoryName,
            'due_at' => self::utc($i->dueAt),
            'returned_at' => $i->returnedAt === null ? null : self::utc($i->returnedAt),
        ];
    }

    /** api-contract.md section 1: ISO 8601 in UTC with Z. */
    private static function utc(DateTimeImmutable $d): string
    {
        return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
