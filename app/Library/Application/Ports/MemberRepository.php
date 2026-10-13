<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\Member;
use DateTimeImmutable;

interface MemberRepository
{
    public function add(Member $member): void;

    public function exists(string $id): bool;

    /** Q7: unreturned lines of a member. */
    public function unreturnedCount(string $id): int;

    /** Q7: whether the member has an unreturned line due before now. */
    public function hasOverdue(string $id, DateTimeImmutable $now): bool;

    /** Member with active_loans (unreturned items count), or null. */
    public function view(string $id): ?array;

    /** @return array{items: list<array>, total: int} */
    public function search(?string $q, int $page, int $size): array;
}
