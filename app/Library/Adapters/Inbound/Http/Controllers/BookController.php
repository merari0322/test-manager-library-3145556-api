<?php

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Application\Services\BookCoverService;
use App\Library\Application\Services\CreateBookService;
use App\Library\Application\Services\DeleteBookService;
use App\Library\Application\Services\ListCategoriesService;
use App\Library\Application\Services\SearchBooksService;
use App\Library\Application\Services\UpdateBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BookController
{
    private const BODY = [
        'title' => ['required', 'string', 'max:250'],
        'author' => ['required', 'string', 'max:200'],
        'category_id' => ['required', 'uuid'],
        'total_copies' => ['required', 'integer'],
    ];

    public function categories(ListCategoriesService $service): JsonResponse
    {
        return response()->json($service->list());
    }

    public function index(Request $request, SearchBooksService $service): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string'],
            'category_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($service->search($data['q'] ?? null, $data['category_id'] ?? null,
            (int) ($data['page'] ?? 1), (int) ($data['size'] ?? 20)));
    }

    public function show(string $id, SearchBooksService $service): JsonResponse
    {
        return response()->json($service->get($id));
    }

    public function store(Request $request, CreateBookService $create, SearchBooksService $search): JsonResponse
    {
        $data = $request->validate(self::BODY + ['isbn' => ['required', 'string']]);
        $id = $create->create($data['title'], $data['author'], $data['isbn'], $data['category_id'], (int) $data['total_copies']);

        return response()->json($search->get($id), 201);
    }

    public function update(string $id, Request $request, UpdateBookService $update, SearchBooksService $search): JsonResponse
    {
        $data = $request->validate(self::BODY);
        $update->update($id, $data['title'], $data['author'], $data['category_id'], (int) $data['total_copies']);

        return response()->json($search->get($id));
    }

    public function uploadCover(string $id, Request $request, BookCoverService $covers, SearchBooksService $search): JsonResponse
    {
        $request->validate(['file' => ['required', 'file']]);
        $covers->upload($id, (string) file_get_contents($request->file('file')->getRealPath()));

        return response()->json($search->get($id));
    }

    public function showCover(string $id, BookCoverService $covers): Response
    {
        $bytes = $covers->read($id);
        $type = str_starts_with($bytes, "\x89PNG") ? 'image/png' : 'image/jpeg';

        return response($bytes, 200, ['Content-Type' => $type]);
    }

    public function removeCover(string $id, BookCoverService $covers): Response
    {
        $covers->remove($id);

        return response()->noContent();
    }

    public function destroy(string $id, DeleteBookService $service): Response
    {
        $service->delete($id);

        return response()->noContent();
    }
}
