<?php

namespace App\Library\Domain\Errors;

use RuntimeException;

/** Base of the business errors; only the HTTP adapter maps them to a status (architecture.md section 5). */
abstract class DomainError extends RuntimeException
{
    public const CODE = '';

    /** @param list<array{field: string, issue: string}> $details */
    public function __construct(string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
