<?php

namespace App\Library\Domain\Errors;

final class ValidationFailed extends DomainError
{
    public const CODE = 'VALIDATION_ERROR';
}
