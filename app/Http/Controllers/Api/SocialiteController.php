<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Get the redirect URL for the given provider.
     */
    public function redirect(string $provider): JsonResponse
    {
        try {
            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            return $this->successResponse('URL redirect OAuth berhasil dibuat.', [
                'provider' => $provider,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Provider OAuth tidak didukung atau konfigurasi belum valid.', null, 400);
        }
    }

    /**
     * Handle the callback from the provider.
     */
    public function callback(string $provider): JsonResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return $this->errorResponse('Autentikasi OAuth gagal atau token tidak valid.', null, 401);
        }

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
            ]
        );

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse('Login OAuth berhasil.', [
            'provider' => $provider,
            'user' => $user,
            'token' => $token,
        ]);
    }
}
