<?php

namespace App\Library\Domain\Errors;

final class ItemAlreadyReturned extends DomainError
{
    public const CODE = 'ITEM_ALREADY_RETURNED';
}
