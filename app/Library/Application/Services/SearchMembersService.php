<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Errors\NotFound;

/** HU-05: list and search members (CA-05.2) and get one with active_loans. */
final class SearchMembersService
{
    public function __construct(private readonly MemberRepository $members)
    {
    }

    public function search(?string $q, int $page, int $size): array
    {
        return $this->members->search($q, $page, $size) + ['page' => $page, 'size' => $size];
    }

    public function get(string $id): array
    {
        return $this->members->view($id) ?? throw new NotFound('El socio no existe.');
    }
}
