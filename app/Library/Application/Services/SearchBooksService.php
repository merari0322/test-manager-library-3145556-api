<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Errors\NotFound;

/** HU-04: search books and get one book; also lists the categories of the catalog. */
final class SearchBooksService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): array
    {
        return $this->books->search($q, $categoryId, $page, $size) + ['page' => $page, 'size' => $size];
    }

    /** CA-04.3: unknown or deleted book is 404. */
    public function get(string $id): array
    {
        return $this->books->view($id) ?? throw new NotFound('El libro no existe.');
    }

    public function categories(): array
    {
        return $this->books->categories();
    }
}
