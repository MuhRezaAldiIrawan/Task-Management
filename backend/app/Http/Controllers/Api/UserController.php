<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }
}
