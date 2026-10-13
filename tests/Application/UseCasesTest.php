<?php

namespace Tests\Application;

require_once __DIR__.'/Fakes.php';

use App\Library\Application\Services\CreateLibrarianService;
use App\Library\Application\Services\LendBooksService;
use App\Library\Application\Services\LoginService;
use App\Library\Application\Services\MostBorrowedReportService;
use App\Library\Application\Services\ReturnItemService;
use App\Library\Domain\Book;
use App\Library\Domain\Errors\BookNotAvailable;
use App\Library\Domain\Errors\InvalidCredentials;
use App\Library\Domain\Errors\InvalidDateRange;
use App\Library\Domain\Errors\ItemAlreadyReturned;
use App\Library\Domain\Errors\MemberHasOverdue;
use App\Library\Domain\Errors\MemberLimitExceeded;
use App\Library\Domain\Errors\UsernameTaken;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\Isbn;
use App\Library\Domain\Member;
use App\Library\Domain\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** Application use cases against fakes, no database (plan.md section 3). */
final class UseCasesTest extends TestCase
{
    private InMemoryBooks $books;
    private InMemoryLoans $loans;
    private InMemoryMembers $members;
    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->books = new InMemoryBooks();
        $this->loans = new InMemoryLoans($this->books);
        $this->members = new InMemoryMembers($this->loans);
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
        $this->members->add(Member::create('m1', 'Ana', '1001'));
        foreach (['b1' => 1, 'b2' => 5, 'b3' => 5, 'b4' => 5] as $id => $copies) {
            $this->books->add(Book::create($id, "Libro $id", 'Autor', Isbn::of('9780000000001'), 'cat', Quantity::of($copies)));
        }
    }

    private function lend(): LendBooksService
    {
        return new LendBooksService($this->members, $this->books, $this->loans, $this->clock);
    }

    public function test_login_same_error_for_wrong_user_or_password(): void
    {
        $users = new InMemoryUsers();
        (new CreateLibrarianService($users, new FakeHasher()))->create('Ana ', 'secret', 'librarian');
        $login = new LoginService($users, new FakeHasher(), new FakeTokens());

        $this->assertSame(28800, $login->login('ana', 'secret')['expires_in']);
        foreach ([['ana', 'bad'], ['nobody', 'secret']] as [$user, $password]) {
            try {
                $login->login($user, $password);
                $this->fail('Debía fallar');
            } catch (InvalidCredentials $e) {
                $this->assertSame('Usuario o contraseña incorrectos.', $e->getMessage());
            }
        }
    }

    public function test_librarian_creation_rules(): void
    {
        $users = new InMemoryUsers();
        $service = new CreateLibrarianService($users, new FakeHasher());
        $service->create('ana', 'secret', 'librarian');

        $this->expectException(UsernameTaken::class);
        $service->create('ANA ', 'other', 'librarian');
    }

    public function test_admin_role_is_never_granted(): void
    {
        $this->expectException(ValidationFailed::class);
        (new CreateLibrarianService(new InMemoryUsers(), new FakeHasher()))->create('bob', 'x', 'admin');
    }

    public function test_lend_withdraws_copies_and_due_in_14_days(): void
    {
        $loan = $this->lend()->lend('m1', 'u1', ['b1', 'b2']);

        $this->assertSame(0, $this->books->get('b1')->availableCopies);
        $this->assertSame(4, $this->books->get('b2')->availableCopies);
        $this->assertEquals(new DateTimeImmutable('2026-10-15T10:00:00Z'), $loan->items[0]->dueAt);
    }

    public function test_no_copies_registers_nothing(): void
    {
        $this->lend()->lend('m1', 'u1', ['b1']);
        $this->members->add(Member::create('m2', 'Luis', '1002'));

        try {
            $this->lend()->lend('m2', 'u1', ['b2', 'b1']);
            $this->fail('Debía fallar');
        } catch (BookNotAvailable) {
            $this->assertSame(5, $this->books->get('b2')->availableCopies);
            $this->assertCount(1, $this->loans->loans);
        }
    }

    public function test_member_limit_of_three(): void
    {
        $this->lend()->lend('m1', 'u1', ['b2', 'b3']);

        $this->expectException(MemberLimitExceeded::class);
        $this->lend()->lend('m1', 'u1', ['b4', 'b1']);
    }

    public function test_member_with_overdue_cannot_borrow(): void
    {
        $this->lend()->lend('m1', 'u1', ['b2']);
        $this->clock->now = new DateTimeImmutable('2026-10-20T10:00:00Z');

        $this->expectException(MemberHasOverdue::class);
        $this->lend()->lend('m1', 'u1', ['b3']);
    }

    public function test_return_restocks_once(): void
    {
        $loan = $this->lend()->lend('m1', 'u1', ['b1']);
        $return = new ReturnItemService($this->loans, $this->clock);
        $return->return($loan->id, $loan->items[0]->id);

        $this->assertSame(1, $this->books->get('b1')->availableCopies);
        $this->expectException(ItemAlreadyReturned::class);
        $return->return($loan->id, $loan->items[0]->id);
    }

    public function test_report_rejects_inverted_range(): void
    {
        $this->expectException(InvalidDateRange::class);
        (new MostBorrowedReportService(new FakeReportReader()))->report('2026-10-31', '2026-10-01');
    }
}
