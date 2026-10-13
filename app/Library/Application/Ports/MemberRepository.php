<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\Member;

interface MemberRepository
{
    public function add(Member $member): void;

    /** Member with active_loans (unreturned items count), or null. */
    public function view(string $id): ?array;

    /** @return array{items: list<array>, total: int} */
    public function search(?string $q, int $page, int $size): array;
}
