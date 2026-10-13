<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\ValidationFailed;

/** CA-03.3: total copies >= 1. */
final class Quantity
{
    private function __construct(public readonly int $value)
    {
    }

    public static function of(int $value): self
    {
        if ($value < 1) {
            throw new ValidationFailed('El total de ejemplares debe ser al menos 1.', [
                ['field' => 'total_copies', 'issue' => 'mínimo 1'],
            ]);
        }

        return new self($value);
    }
}
