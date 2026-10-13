<?php

namespace Tests\Contract;

use PHPUnit\Framework\TestCase;

/**
 * Contract (RNF-4, T-16) and concurrency (RNF-2, T-13) tests against the running API.
 * Run with the stack up: API_URL, ADMIN_USERNAME and ADMIN_PASSWORD come from the environment.
 */
final class ContractTest extends TestCase
{
    private static string $api;
    private static string $admin;
    private static string $librarian;
    private const CATEGORY = '11111111-1111-4111-8111-111111111111';

    public static function setUpBeforeClass(): void
    {
        self::$api = getenv('API_URL') ?: 'http://localhost:8000/api/v1';
    }

    protected function setUp(): void
    {
        if (! getenv('ADMIN_USERNAME') || ! getenv('ADMIN_PASSWORD')) {
            $this->markTestSkipped('Definir ADMIN_USERNAME y ADMIN_PASSWORD para correr las pruebas de contrato.');
        }
        if (! isset(self::$admin)) {
            self::$admin = $this->call('POST', '/auth/login', null,
                ['username' => getenv('ADMIN_USERNAME'), 'password' => getenv('ADMIN_PASSWORD')])['body']['access_token'];
            $name = 'lib'.bin2hex(random_bytes(3));
            $this->call('POST', '/users', self::$admin, ['username' => $name, 'password' => 'secret123', 'role' => 'librarian']);
            self::$librarian = $this->call('POST', '/auth/login', null, ['username' => $name, 'password' => 'secret123'])['body']['access_token'];
        }
    }

    /** @return array{status: int, body: mixed} */
    private function call(string $method, string $path, ?string $token, ?array $body = null): array
    {
        $ch = $this->handle($method, $path, $token, $body);
        $raw = curl_exec($ch);

        return ['status' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => json_decode((string) $raw, true)];
    }

    private function handle(string $method, string $path, ?string $token, ?array $body)
    {
        $ch = curl_init(self::$api.$path);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer '.$token;
        }
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        return $ch;
    }

    private function assertError(array $res, int $status, string $code): void
    {
        $this->assertSame($status, $res['status']);
        $this->assertSame(['code', 'message', 'details'], array_keys($res['body']), 'cuerpo de error único (RNF-4)');
        $this->assertSame($code, $res['body']['code']);
    }

    private function newBook(int $copies): string
    {
        $isbn = '979'.str_pad((string) random_int(0, 9_999_999_999), 10, '0', STR_PAD_LEFT);

        return $this->call('POST', '/books', self::$admin,
            ['title' => 'Contrato '.$isbn, 'author' => 'Autor', 'isbn' => $isbn, 'category_id' => self::CATEGORY, 'total_copies' => $copies])['body']['id'];
    }

    private function newMember(): string
    {
        return $this->call('POST', '/members', self::$librarian,
            ['full_name' => 'Socio contrato', 'document_number' => 'C-'.bin2hex(random_bytes(4))])['body']['id'];
    }

    public function test_public_and_protected_routes(): void
    {
        $this->assertSame(['status' => 'ok'], $this->call('GET', '/health', null)['body']);
        $this->assertError($this->call('GET', '/books', null), 401, 'INVALID_CREDENTIALS');
        $this->assertError($this->call('POST', '/auth/login', null, ['username' => 'x', 'password' => 'y']), 401, 'INVALID_CREDENTIALS');
        $this->assertError($this->call('POST', '/books', self::$librarian, []), 403, 'FORBIDDEN_ROLE');
        $this->assertError($this->call('GET', '/books/'.'00000000-0000-4000-8000-000000000000', self::$admin), 404, 'NOT_FOUND');
        $this->assertError($this->call('POST', '/users', self::$admin, []), 422, 'VALIDATION_ERROR');
    }

    public function test_every_success_route_answers(): void
    {
        $today = gmdate('Y-m-d');
        foreach (['/categories', '/books', '/members', '/loans', '/loans/overdue', "/reports/most-borrowed?from=$today&to=$today"] as $path) {
            $this->assertSame(200, $this->call('GET', $path, self::$admin)['status'], $path);
        }
        $this->assertCount(5, $this->call('GET', '/categories', self::$admin)['body']);
    }

    public function test_business_error_codes(): void
    {
        $book = $this->newBook(1);
        $isbn = $this->call('GET', "/books/$book", self::$admin)['body']['isbn'];
        $this->assertError($this->call('POST', '/books', self::$admin,
            ['title' => 'X', 'author' => 'Y', 'isbn' => $isbn, 'category_id' => self::CATEGORY, 'total_copies' => 1]), 409, 'ISBN_TAKEN');

        $member = $this->newMember();
        $document = $this->call('GET', "/members/$member", self::$admin)['body']['document_number'];
        $this->assertError($this->call('POST', '/members', self::$admin, ['full_name' => 'Otro', 'document_number' => $document]), 409, 'DOCUMENT_TAKEN');

        $this->assertError($this->call('POST', '/loans', self::$librarian,
            ['member_id' => $member, 'items' => [['book_id' => $book], ['book_id' => $book]]]), 422, 'DUPLICATE_BOOK_IN_LOAN');

        $loan = $this->call('POST', '/loans', self::$librarian, ['member_id' => $member, 'items' => [['book_id' => $book]]]);
        $this->assertSame(201, $loan['status']);
        $this->assertError($this->call('DELETE', "/books/$book", self::$admin), 409, 'BOOK_HAS_OPEN_LOANS');
        $this->assertError($this->call('POST', '/loans', self::$librarian, ['member_id' => $this->newMember(), 'items' => [['book_id' => $book]]]), 409, 'BOOK_NOT_AVAILABLE');

        $item = $loan['body']['items'][0]['id'];
        $this->assertSame(200, $this->call('POST', "/loans/{$loan['body']['id']}/items/$item/return", self::$librarian)['status']);
        $this->assertError($this->call('POST', "/loans/{$loan['body']['id']}/items/$item/return", self::$librarian), 409, 'ITEM_ALREADY_RETURNED');

        $books = [$this->newBook(5), $this->newBook(5), $this->newBook(5), $this->newBook(5)];
        $this->call('POST', '/loans', self::$librarian, ['member_id' => $member, 'items' => [['book_id' => $books[0]], ['book_id' => $books[1]]]]);
        $this->assertError($this->call('POST', '/loans', self::$librarian,
            ['member_id' => $member, 'items' => [['book_id' => $books[2]], ['book_id' => $books[3]]]]), 422, 'MEMBER_LIMIT_EXCEEDED');

        $this->assertError($this->call('GET', '/reports/most-borrowed?from=2026-10-31&to=2026-10-01', self::$admin), 422, 'INVALID_DATE_RANGE');
        $this->assertError($this->call('GET', '/reports/most-borrowed?from=2026-10-01&to=2026-10-31', self::$librarian), 403, 'FORBIDDEN_ROLE');
    }

    /** RNF-2: two loans of the last copy at the same time, exactly one wins. */
    public function test_last_copy_concurrency(): void
    {
        $book = $this->newBook(1);
        $multi = curl_multi_init();
        $handles = [];
        foreach ([$this->newMember(), $this->newMember()] as $member) {
            $h = $this->handle('POST', '/loans', self::$librarian, ['member_id' => $member, 'items' => [['book_id' => $book]]]);
            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($multi, $h);
            $handles[] = $h;
        }
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running > 0);

        $statuses = array_map(fn ($h) => curl_getinfo($h, CURLINFO_HTTP_CODE), $handles);
        sort($statuses);
        $this->assertSame([201, 409], $statuses);
        $this->assertSame(0, $this->call('GET', "/books/$book", self::$admin)['body']['available_copies']);
    }
}
