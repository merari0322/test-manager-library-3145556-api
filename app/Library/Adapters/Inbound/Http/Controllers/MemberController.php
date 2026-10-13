<?php

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Application\Services\CreateMemberService;
use App\Library\Application\Services\SearchMembersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MemberController
{
    public function index(Request $request, SearchMembersService $service): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($service->search($data['q'] ?? null, (int) ($data['page'] ?? 1), (int) ($data['size'] ?? 20)));
    }

    public function show(string $id, SearchMembersService $service): JsonResponse
    {
        return response()->json($service->get($id));
    }

    public function store(Request $request, CreateMemberService $create, SearchMembersService $search): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ]);
        $id = $create->create($data['full_name'], $data['document_number']);

        return response()->json($search->get($id), 201);
    }
}
