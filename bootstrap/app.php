<?php

use App\Library\Adapters\Inbound\Http\ErrorResponder;
use App\Library\Adapters\Inbound\Http\Middleware\Authenticate;
use App\Library\Bootstrap\CreateInitialAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
    )
    ->withCommands([CreateInitialAdmin::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['library.auth' => Authenticate::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (Throwable $e) => ErrorResponder::render($e));
    })->create();
