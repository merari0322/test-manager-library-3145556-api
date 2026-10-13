<?php

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Application\Services\CreateLibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController
{
    public function store(Request $request, CreateLibrarianService $service): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string'],
        ]);

        $user = $service->create($data['username'], $data['password'], $data['role']);

        // CA-02.3: never return password nor password_hash.
        return response()->json(['id' => $user->id, 'username' => $user->username, 'role' => $user->role], 201);
    }
}
