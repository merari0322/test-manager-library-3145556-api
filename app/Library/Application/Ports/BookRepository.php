<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\Book;

interface BookRepository
{
    /** Active book (deleted_at IS NULL) or null. */
    public function get(string $id): ?Book;

    public function add(Book $book): void;

    public function save(Book $book): void;

    public function hasUnreturnedItems(string $bookId): bool;

    public function categoryExists(string $categoryId): bool;

    /** Q4: the five categories ordered by name. @return list<array{id: string, name: string}> */
    public function categories(): array;

    /** Q2: active book as response row, or null. */
    public function view(string $id): ?array;

    /** Q1: @return array{items: list<array>, total: int} */
    public function search(?string $q, ?string $categoryId, int $page, int $size): array;
}
