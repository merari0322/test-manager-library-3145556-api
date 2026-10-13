<?php

namespace App\Library\Bootstrap;

use App\Library\Adapters\Outbound\Persistence\DbUserRepository;
use App\Library\Adapters\Outbound\Security\BcryptPasswordHasher;
use App\Library\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;
use Illuminate\Support\ServiceProvider;

/** Article III: the only place where a port meets its adapter. */
final class PortBindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepository::class, DbUserRepository::class);
        $this->app->singleton(PasswordHasher::class, BcryptPasswordHasher::class);
        $this->app->singleton(TokenIssuer::class, fn () => new JwtTokenIssuer((string) env('JWT_SECRET')));
    }
}
