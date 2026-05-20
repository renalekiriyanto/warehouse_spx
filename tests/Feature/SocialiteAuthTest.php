<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialiteAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_returns_url()
    {
        // Mock Socialite redirect
        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('redirect->getTargetUrl')->andReturn('https://provider.com/auth');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/auth/google/redirect');

        $response->assertStatus(200)
                 ->assertJson(['url' => 'https://provider.com/auth']);
    }

    public function test_callback_creates_user_and_returns_token()
    {
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '12345';
        $socialiteUser->name = 'Test User';
        $socialiteUser->email = 'test@example.com';
        $socialiteUser->token = 'dummy-token';

        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user' => [
                         'id', 'name', 'email', 'provider_name', 'provider_id'
                     ],
                     'token'
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'provider_name' => 'google',
            'provider_id' => '12345',
        ]);
    }
}
