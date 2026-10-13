<?php

namespace Tests\Application;

use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\ReportReader;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Book;
use App\Library\Domain\DateRange;
use App\Library\Domain\Loan;
use App\Library\Domain\LoanItem;
use App\Library\Domain\Member;
use App\Library\Domain\User;
use DateTimeImmutable;

/** In-memory fakes of the ports (plan.md section 3: application tests run against fakes). */
final class FixedClock implements Clock
{
    public function __construct(public DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === 'hashed:'.$plain;
    }
}

final class FakeTokens implements TokenIssuer
{
    public function issue(User $user): string
    {
        return 'token-'.$user->id;
    }

    public function read(string $token): ?array
    {
        return null;
    }
}

final class InMemoryUsers implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];

    public function findByUsername(string $username): ?User
    {
        return $this->users[$username] ?? null;
    }

    public function add(User $user): void
    {
        $this->users[$user->username] = $user;
    }
}

final class InMemoryBooks implements BookRepository
{
    /** @var array<string, Book> */
    public array $books = [];

    /** Returns a copy, as a real repository would: changes only count after save. */
    public function get(string $id): ?Book
    {
        return isset($this->books[$id]) ? clone $this->books[$id] : null;
    }

    public function add(Book $book): void
    {
        $this->books[$book->id] = clone $book;
    }

    public function save(Book $book): void
    {
        $this->books[$book->id] = clone $book;
    }

    public function hasUnreturnedItems(string $bookId): bool
    {
        return false;
    }

    public function categoryExists(string $categoryId): bool
    {
        return true;
    }

    public function categoryName(string $categoryId): string
    {
        return 'Novela';
    }

    public function categories(): array
    {
        return [];
    }

    public function view(string $id): ?array
    {
        return null;
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): array
    {
        return ['items' => [], 'total' => 0];
    }
}

final class InMemoryLoans implements LoanRepository
{
    /** @var array<string, Loan> */
    public array $loans = [];

    public function __construct(private readonly InMemoryBooks $books)
    {
    }

    public function get(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function add(Loan $loan, array $books): void
    {
        foreach ($books as $book) {
            $this->books->save($book);
        }
        $this->loans[$loan->id] = $loan;
    }

    public function saveReturn(Loan $loan, LoanItem $item): void
    {
        $book = $this->books->get($item->bookId);
        $book->restock();
        $this->books->save($book);
    }

    public function search(?DateRange $range, ?string $status, ?string $memberId, DateTimeImmutable $now, int $page, int $size): array
    {
        return ['items' => array_values($this->loans), 'total' => count($this->loans)];
    }

    public function overdue(DateTimeImmutable $now): array
    {
        return [];
    }

    /** @return list<LoanItem> */
    public function unreturnedOf(string $memberId): array
    {
        $items = [];
        foreach ($this->loans as $loan) {
            if ($loan->memberId === $memberId) {
                foreach ($loan->items as $item) {
                    if ($item->returnedAt === null) {
                        $items[] = $item;
                    }
                }
            }
        }

        return $items;
    }
}

final class InMemoryMembers implements MemberRepository
{
    /** @var array<string, Member> */
    public array $members = [];

    public function __construct(private readonly InMemoryLoans $loans)
    {
    }

    public function add(Member $member): void
    {
        $this->members[$member->id] = $member;
    }

    public function exists(string $id): bool
    {
        return isset($this->members[$id]);
    }

    public function unreturnedCount(string $id): int
    {
        return count($this->loans->unreturnedOf($id));
    }

    public function hasOverdue(string $id, DateTimeImmutable $now): bool
    {
        foreach ($this->loans->unreturnedOf($id) as $item) {
            if ($item->dueAt < $now) {
                return true;
            }
        }

        return false;
    }

    public function view(string $id): ?array
    {
        return null;
    }

    public function search(?string $q, int $page, int $size): array
    {
        return ['items' => [], 'total' => 0];
    }
}

final class FakeReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        return [];
    }
}
