<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Book;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\Isbn;
use App\Library\Domain\Quantity;
use App\Library\Domain\Uuid;

/** HU-03: create a book (CA-03.1, CA-03.2). */
final class CreateBookService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function create(string $title, string $author, string $isbn, string $categoryId, int $totalCopies): string
    {
        if (! $this->books->categoryExists($categoryId)) {
            throw new ValidationFailed('La categoría no existe.', [['field' => 'category_id', 'issue' => 'no existe']]);
        }

        $book = Book::create(Uuid::v4(), $title, $author, Isbn::of($isbn), $categoryId, Quantity::of($totalCopies));
        $this->books->add($book);

        return $book->id;
    }
}
