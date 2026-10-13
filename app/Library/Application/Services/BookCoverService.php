<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\CoverStorage;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\Uuid;

/** CA-03.4: upload, read and remove a book cover (JPG/PNG, max 2 MB, opaque key). */
final class BookCoverService
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(private readonly BookRepository $books, private readonly CoverStorage $storage)
    {
    }

    public function upload(string $bookId, string $bytes): void
    {
        $book = $this->books->get($bookId) ?? throw new NotFound('El libro no existe.');
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new ValidationFailed('La portada supera 2 MB.', [['field' => 'file', 'issue' => 'máximo 2 MB']]);
        }
        // api-contract.md section 4: the type is checked by content, not by extension.
        if (! str_starts_with($bytes, "\x89PNG\r\n\x1a\n") && ! str_starts_with($bytes, "\xFF\xD8\xFF")) {
            throw new ValidationFailed('La portada debe ser JPG o PNG.', [['field' => 'file', 'issue' => 'solo JPG o PNG']]);
        }

        $old = $book->coverKey;
        $book->coverKey = Uuid::v4();
        $this->storage->put($book->coverKey, $bytes);
        $this->books->save($book);
        if ($old !== null) {
            $this->storage->delete($old);
        }
    }

    public function read(string $bookId): string
    {
        $book = $this->books->get($bookId) ?? throw new NotFound('El libro no existe.');
        $bytes = $book->coverKey === null ? null : $this->storage->get($book->coverKey);

        return $bytes ?? throw new NotFound('El libro no tiene portada.');
    }

    /** data-model.md section 7: first null cover_key and commit, then delete the binary. */
    public function remove(string $bookId): void
    {
        $book = $this->books->get($bookId) ?? throw new NotFound('El libro no existe.');
        $key = $book->coverKey;
        $book->coverKey = null;
        $this->books->save($book);
        if ($key !== null) {
            $this->storage->delete($key);
        }
    }
}
