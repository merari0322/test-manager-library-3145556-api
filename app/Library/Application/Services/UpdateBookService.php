<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\Quantity;

/** HU-03: replace title, author, category and total (CA-03.3). */
final class UpdateBookService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function update(string $id, string $title, string $author, string $categoryId, int $totalCopies): void
    {
        $book = $this->books->get($id) ?? throw new NotFound('El libro no existe.');
        if (! $this->books->categoryExists($categoryId)) {
            throw new ValidationFailed('La categoría no existe.', [['field' => 'category_id', 'issue' => 'no existe']]);
        }

        $book->update($title, $author, $categoryId, Quantity::of($totalCopies));
        $this->books->save($book);
    }
}
