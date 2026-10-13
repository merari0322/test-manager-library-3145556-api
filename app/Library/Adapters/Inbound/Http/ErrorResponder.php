<?php

namespace App\Library\Adapters\Inbound\Http;

use App\Library\Domain\Errors\DomainError;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** RNF-4: single error body { code, message, details } (api-contract.md sections 1 and 2). */
final class ErrorResponder
{
    private const STATUS = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'ISBN_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'BOOK_HAS_OPEN_LOANS' => 409,
        'BOOK_NOT_AVAILABLE' => 409,
        'MEMBER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'MEMBER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_BOOK_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    // Article XI: user messages in Spanish.
    private const ISSUES = [
        'Required' => 'obligatorio',
        'String' => 'debe ser texto',
        'Integer' => 'debe ser un número entero',
        'Max' => 'supera el máximo permitido',
        'Min' => 'está por debajo del mínimo permitido',
        'Uuid' => 'debe ser un UUID',
        'Date' => 'debe ser una fecha',
        'Array' => 'debe ser una lista',
    ];

    /** Returns null for errors the contract does not define (see ADR-005, INC-20). */
    public static function render(Throwable $e): ?JsonResponse
    {
        if ($e instanceof DomainError) {
            return self::body($e::CODE, $e->getMessage(), $e->details, self::STATUS[$e::CODE]);
        }

        if ($e instanceof ValidationException) {
            $details = [];
            foreach ($e->validator->failed() as $field => $rules) {
                foreach (array_keys($rules) as $rule) {
                    $details[] = ['field' => $field, 'issue' => self::ISSUES[$rule] ?? 'valor inválido'];
                }
            }

            return self::body('VALIDATION_ERROR', 'Hay campos inválidos.', $details, 422);
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 404) {
            return self::body('NOT_FOUND', 'El recurso no existe.', [], 404);
        }

        return null;
    }

    private static function body(string $code, string $message, array $details, int $status): JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message, 'details' => $details], $status);
    }
}
