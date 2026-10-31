<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /** @return array{0: User, 1: string} */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'customer',  
        ]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** @return array{0: User, 1: string}|null */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [$user, $user->createToken('api')->plainTextToken];
    }
}