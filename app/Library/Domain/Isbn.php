<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\ValidationFailed;

/** CA-03.2: 13 digits. */
final class Isbn
{
    private function __construct(public readonly string $value)
    {
    }

    public static function of(string $value): self
    {
        if (preg_match('/^\d{13}$/', $value) !== 1) {
            throw new ValidationFailed('El ISBN debe tener 13 dígitos.', [['field' => 'isbn', 'issue' => '13 dígitos']]);
        }

        return new self($value);
    }
}
