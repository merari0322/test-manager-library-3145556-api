<?php

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Book;
use App\Library\Domain\Errors\IsbnTaken;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbBookRepository implements BookRepository
{
    public function get(string $id): ?Book
    {
        $row = $this->active()->where('id', $id)->first();

        return $row === null ? null : Book::restore($row->id, $row->title, $row->author, $row->isbn, $row->category_id,
            $row->total_copies, $row->available_copies, $row->cover_key,
            $row->deleted_at === null ? null : new DateTimeImmutable($row->deleted_at));
    }

    public function add(Book $book): void
    {
        try {
            DB::table('book')->insert(['id' => $book->id, 'isbn' => $book->isbn->value] + $this->columns($book));
        } catch (UniqueConstraintViolationException) {
            throw new IsbnTaken('Ya existe un libro activo con ese ISBN.');
        }
    }

    public function save(Book $book): void
    {
        DB::table('book')->where('id', $book->id)->update($this->columns($book));
    }

    public function hasUnreturnedItems(string $bookId): bool
    {
        return DB::table('loan_item')->where('book_id', $bookId)->whereNull('returned_at')->exists();
    }

    public function categoryExists(string $categoryId): bool
    {
        return DB::table('category')->where('id', $categoryId)->exists();
    }

    public function categoryName(string $categoryId): string
    {
        return (string) DB::table('category')->where('id', $categoryId)->value('name');
    }

    public function categories(): array
    {
        return DB::table('category')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
    }

    public function view(string $id): ?array
    {
        $row = $this->withCategory()->where('b.id', $id)->first();

        return $row === null ? null : $this->toArray($row);
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): array
    {
        $query = $this->withCategory();
        if ($q !== null && $q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('b.title', 'ilike', $like)->orWhere('b.author', 'ilike', $like));
        }
        if ($categoryId !== null) {
            $query->where('b.category_id', $categoryId);
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('b.title')->offset(($page - 1) * $size)->limit($size)->get();

        return ['items' => $rows->map(fn ($r) => $this->toArray($r))->all(), 'total' => $total];
    }

    private function active(): Builder
    {
        return DB::table('book')->whereNull('deleted_at');
    }

    private function withCategory(): Builder
    {
        return DB::table('book as b')->join('category as c', 'c.id', '=', 'b.category_id')
            ->whereNull('b.deleted_at')
            ->select('b.*', 'c.name as category_name');
    }

    private function columns(Book $book): array
    {
        return [
            'title' => $book->title,
            'author' => $book->author,
            'category_id' => $book->categoryId,
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
            'deleted_at' => $book->deletedAt?->format(DATE_ATOM),
        ];
    }

    private function toArray(object $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'author' => $r->author,
            'isbn' => $r->isbn,
            'category' => ['id' => $r->category_id, 'name' => $r->category_name],
            'total_copies' => $r->total_copies,
            'available_copies' => $r->available_copies,
            'cover_key' => $r->cover_key,
        ];
    }
}
