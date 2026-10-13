<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;

/** Q4: the five categories ordered by name. */
final class ListCategoriesService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function list(): array
    {
        return $this->books->categories();
    }
}
