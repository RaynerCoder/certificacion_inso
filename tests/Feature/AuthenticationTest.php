<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(config('fortify.home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['estado' => 0]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_authenticate_again_after_reactivation(): void
    {
        $user = User::factory()->create(['estado' => 0]);
        $user->update(['estado' => 1]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(config('fortify.home'));
    }

    public function test_session_is_closed_when_user_becomes_inactive(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->actingAs($user);

        $user->update(['estado' => 0]);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        // Obliga a la prueba a leer nuevamente la sesion invalidada por el middleware.
        $this->app['auth']->forgetGuards();
        $this->assertGuest();
    }
}
