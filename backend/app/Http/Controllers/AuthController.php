<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Authentication Controller
 *
 * Handles user authentication including login, logout, and token management.
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User login",
     *     description="Authenticate user and receive JWT token",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="User logout",
     *     description="Invalidate current JWT token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Successfully logged out"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Get current user",
     *     description="Get authenticated user details",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="User details retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Refresh JWT token",
     *     description="Get a new JWT token using current token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Token refreshed successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }
}
