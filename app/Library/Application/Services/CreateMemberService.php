<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Member;
use App\Library\Domain\Uuid;

/** HU-05: create a member (CA-05.1). */
final class CreateMemberService
{
    public function __construct(private readonly MemberRepository $members)
    {
    }

    public function create(string $fullName, string $documentNumber): string
    {
        $member = Member::create(Uuid::v4(), $fullName, $documentNumber);
        $this->members->add($member);

        return $member->id;
    }
}
