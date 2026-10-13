<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\BookNotAvailable;
use App\Library\Domain\Errors\ValidationFailed;
use DateTimeImmutable;

/** Aggregate root: available copies between 0 and total; total >= 1; title not empty (architecture.md section 3). */
final class Book
{
    private function __construct(
        public readonly string $id,
        public string $title,
        public string $author,
        public readonly Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey,
        public ?DateTimeImmutable $deletedAt,
    ) {
    }

    /** CA-03.1: available copies start equal to the total. */
    public static function create(string $id, string $title, string $author, Isbn $isbn, string $categoryId, Quantity $total): self
    {
        return new self($id, self::requireText($title, 'title'), self::requireText($author, 'author'),
            $isbn, $categoryId, $total->value, $total->value, null, null);
    }

    public static function restore(string $id, string $title, string $author, string $isbn, string $categoryId,
        int $total, int $available, ?string $coverKey, ?DateTimeImmutable $deletedAt): self
    {
        return new self($id, $title, $author, Isbn::of($isbn), $categoryId, $total, $available, $coverKey, $deletedAt);
    }

    /** CA-03.3: changing the total moves available copies by the same difference, never below 0. */
    public function update(string $title, string $author, string $categoryId, Quantity $total): void
    {
        $available = $this->availableCopies + ($total->value - $this->totalCopies);
        if ($available < 0) {
            throw new ValidationFailed('El nuevo total deja disponibles por debajo de 0.', [
                ['field' => 'total_copies', 'issue' => 'hay ejemplares prestados'],
            ]);
        }

        $this->title = self::requireText($title, 'title');
        $this->author = self::requireText($author, 'author');
        $this->categoryId = $categoryId;
        $this->totalCopies = $total->value;
        $this->availableCopies = $available;
    }

    /** CA-06.3: each loan line takes one available copy. */
    public function withdraw(): void
    {
        if ($this->availableCopies < 1) {
            throw new BookNotAvailable("No quedan ejemplares disponibles de «{$this->title}».");
        }
        $this->availableCopies--;
    }

    /** CA-07.1 and CA-07.3: a return adds one copy, never above the total. */
    public function restock(): void
    {
        if ($this->availableCopies < $this->totalCopies) {
            $this->availableCopies++;
        }
    }

    /** CA-03.5: logical delete. */
    public function delete(DateTimeImmutable $now): void
    {
        $this->deletedAt = $now;
    }

    private static function requireText(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new ValidationFailed('Hay campos inválidos.', [['field' => $field, 'issue' => 'obligatorio']]);
        }

        return $value;
    }
}
