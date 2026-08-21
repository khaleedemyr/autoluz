<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use App\Services\WishlistService;
use App\Support\GuestSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_]+$/',
                'unique:'.User::class,
            ],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.regex' => 'Username hanya boleh huruf kecil, angka, dan underscore.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => Str::lower($data['username']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => Role::visitor()?->id,
            'is_admin' => false,
        ]);

        event(new Registered($user));
        $this->mergeGuest($user, $request);

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->mergeGuest($user, $request);
        $user->tokens()->where('name', 'autoluz-app')->delete();

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->toAuthArray(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_]+$/',
                'unique:users,username,'.$user->id,
            ],
            'bio' => ['nullable', 'string', 'max:280'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,'.$user->id,
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'username' => Str::lower($data['username']),
            'bio' => $data['bio'] ?? null,
            'email' => $data['email'],
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('community/avatars', 'public');
        }

        $user->save();

        return response()->json(['user' => $user->fresh()->toAuthArray()]);
    }

    private function mergeGuest(User $user, Request $request): void
    {
        $guestId = GuestSession::id($request);
        app(CartService::class)->mergeGuestCartIntoUser($user, $guestId);
        app(WishlistService::class)->mergeGuestWishlistIntoUser($user, $guestId);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('autoluz-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->fresh()->toAuthArray(),
        ], $status);
    }
}
