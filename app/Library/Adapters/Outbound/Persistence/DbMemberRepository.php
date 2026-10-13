<?php

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Errors\DocumentTaken;
use App\Library\Domain\Member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbMemberRepository implements MemberRepository
{
    public function add(Member $member): void
    {
        try {
            DB::table('member')->insert([
                'id' => $member->id,
                'full_name' => $member->fullName,
                'document_number' => $member->documentNumber,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DocumentTaken('Ya existe un socio con ese documento.');
        }
    }

    public function view(string $id): ?array
    {
        $row = DB::table('member')->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        $active = DB::table('loan_item as i')->join('loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.member_id', $id)->whereNull('i.returned_at')->count();

        return $this->toArray($row) + ['active_loans' => $active];
    }

    public function search(?string $q, int $page, int $size): array
    {
        $query = DB::table('member');
        if ($q !== null && $q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('full_name', 'ilike', $like)->orWhere('document_number', 'ilike', $like));
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('full_name')->offset(($page - 1) * $size)->limit($size)->get();

        return ['items' => $rows->map(fn ($r) => $this->toArray($r))->all(), 'total' => $total];
    }

    private function toArray(object $r): array
    {
        return ['id' => $r->id, 'full_name' => $r->full_name, 'document_number' => $r->document_number];
    }
}
