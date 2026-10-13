<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Domain\Errors\BookHasOpenLoans;
use App\Library\Domain\Errors\NotFound;

/** HU-03: logical delete (CA-03.5, ADR-003). Open loans read as unreturned items (see ADR-005, INC-07). */
final class DeleteBookService
{
    public function __construct(private readonly BookRepository $books, private readonly Clock $clock)
    {
    }

    public function delete(string $id): void
    {
        $book = $this->books->get($id) ?? throw new NotFound('El libro no existe.');
        if ($this->books->hasUnreturnedItems($id)) {
            throw new BookHasOpenLoans('El libro tiene préstamos abiertos y no se puede dar de baja.');
        }

        $book->delete($this->clock->now());
        $this->books->save($book);
    }
}
