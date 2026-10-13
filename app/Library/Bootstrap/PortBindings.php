<?php

namespace App\Library\Bootstrap;

use App\Library\Adapters\Outbound\Persistence\DbBookRepository;
use App\Library\Adapters\Outbound\Persistence\DbLoanRepository;
use App\Library\Adapters\Outbound\Persistence\DbMemberRepository;
use App\Library\Adapters\Outbound\Persistence\DbReportReader;
use App\Library\Adapters\Outbound\Persistence\DbUserRepository;
use App\Library\Adapters\Outbound\Security\BcryptPasswordHasher;
use App\Library\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Library\Adapters\Outbound\SystemClock;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\ReportReader;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;
use Illuminate\Support\ServiceProvider;

/** Article III: the only place where a port meets its adapter. */
final class PortBindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepository::class, DbUserRepository::class);
        $this->app->singleton(BookRepository::class, DbBookRepository::class);
        $this->app->singleton(MemberRepository::class, DbMemberRepository::class);
        $this->app->singleton(LoanRepository::class, DbLoanRepository::class);
        $this->app->singleton(ReportReader::class, DbReportReader::class);
        $this->app->singleton(PasswordHasher::class, BcryptPasswordHasher::class);
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(TokenIssuer::class, fn () => new JwtTokenIssuer((string) env('JWT_SECRET')));
    }
}
