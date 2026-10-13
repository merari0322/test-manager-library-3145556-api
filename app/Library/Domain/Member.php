<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\ValidationFailed;

/** Aggregate root: document required and unique (architecture.md section 3). Members are never deleted (CA-05.3). */
final class Member
{
    private function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $documentNumber,
    ) {
    }

    public static function create(string $id, string $fullName, string $documentNumber): self
    {
        $fullName = trim($fullName);
        $documentNumber = trim($documentNumber);
        if ($fullName === '' || $documentNumber === '') {
            throw new ValidationFailed('Nombre y documento son obligatorios.', [
                ['field' => $fullName === '' ? 'full_name' : 'document_number', 'issue' => 'obligatorio'],
            ]);
        }

        return new self($id, $fullName, $documentNumber);
    }
}
