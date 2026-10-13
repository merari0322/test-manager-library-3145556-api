<?php

namespace App\Library\Adapters\Inbound\Http\Middleware;

use App\Library\Application\Ports\TokenIssuer;
use App\Library\Domain\Errors\ForbiddenRole;
use App\Library\Domain\Errors\InvalidCredentials;
use Closure;
use Illuminate\Http\Request;

/** CA-01.3: token required (401) and role checked (403). Usage: library.auth:admin,librarian */
final class Authenticate
{
    public function __construct(private readonly TokenIssuer $tokens)
    {
    }

    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $token = $request->bearerToken();
        $claims = $token ? $this->tokens->read($token) : null;
        if ($claims === null) {
            throw new InvalidCredentials('Se requiere un token válido.');
        }
        if ($roles !== [] && ! in_array($claims['role'], $roles, true)) {
            throw new ForbiddenRole('Tu rol no permite esta operación.');
        }

        $request->attributes->set('user_id', $claims['sub']);

        return $next($request);
    }
}
