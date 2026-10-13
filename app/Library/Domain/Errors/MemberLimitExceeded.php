<?php

namespace App\Library\Domain\Errors;

final class MemberLimitExceeded extends DomainError
{
    public const CODE = 'MEMBER_LIMIT_EXCEEDED';
}
