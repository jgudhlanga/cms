<?php

namespace App\Http\Controllers\Api\V1\Authentication;

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::guard('web')->validate($request->only('email', 'password'))) {
            $request->hitLoginRateLimit();

            return response()->json([
                'success' => false,
                'data' => [
                    'token' => null,
                    'invalidCredentials' => true,
                    'user' => null,
                ],
            ]);
        }

        $request->clearLoginRateLimit();

        $user = User::where('email', $request->string('email')->toString())->firstOrFail();

        if ((int) $user->status_id === StatusEnum::INACTIVE->id()) {
            return response()->json([
                'success' => false,
                'data' => [
                    'token' => null,
                    'invalidCredentials' => false,
                    'accountInactive' => true,
                    'user' => null,
                ],
            ]);
        }

        $user->load(['tenant', 'status', 'roles', 'studentProfile']);
        Auth::login($user);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'invalidCredentials' => false,
                'user' => UserResource::make($user),
            ],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            'status_id' => StatusEnum::ACTIVE->id(),
            'first_name' => $request->string('first_name')->toString(),
            'last_name' => $request->string('last_name')->toString(),
            'middle_name' => $request->filled('middle_name') ? $request->string('middle_name')->toString() : null,
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->password,
        ]);

        $user->assignRole(RoleEnum::STUDENT);

        $user->email_verified_at = now();
        $user->save();

        $user->load(['tenant', 'status', 'roles', 'studentProfile']);
        Auth::login($user);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'invalidCredentials' => false,
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => __($status),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
