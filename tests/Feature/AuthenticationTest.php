<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Código de seguridad');
        $response->assertSee(route('captcha.login'), false);
    }

    public function test_login_captcha_is_returned_as_a_png_image(): void
    {
        $response = $this->get(route('captcha.login'));
        $imageInfo = getimagesizefromstring($response->getContent());

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $response->assertHeader('x-content-type-options', 'nosniff');
        $response->assertSessionHas(LoginCaptcha::SESSION_KEY);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());
        $this->assertSame([400, 80], [$imageInfo[0], $imageInfo[1]]);
    }

    public function test_login_captcha_can_only_be_used_once(): void
    {
        config()->set('login_captcha.characters', 'A');
        $captcha = app(LoginCaptcha::class);
        $session = $this->app['session.store'];

        $captcha->issue($session);

        $this->assertTrue($captcha->verify($session, 'AAAAAA'));
        $this->assertFalse($captcha->verify($session, 'AAAAAA'));
    }

    public function test_captcha_is_required_before_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('captcha');
    }

    public function test_invalid_captcha_prevents_authentication(): void
    {
        $user = User::factory()->create();
        $this->mockCaptcha(false);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'ABC234',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('captcha');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();
        $this->mockCaptcha();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'ABC234',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(config('fortify.home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();
        $this->mockCaptcha();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha' => 'ABC234',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['estado' => 0]);
        $this->mockCaptcha();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'ABC234',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_authenticate_again_after_reactivation(): void
    {
        $user = User::factory()->create(['estado' => 0]);
        $user->update(['estado' => 1]);
        $this->mockCaptcha();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'ABC234',
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

    private function mockCaptcha(bool $isValid = true): void
    {
        $this->mock(LoginCaptcha::class, function (MockInterface $mock) use ($isValid) {
            $mock->shouldReceive('verify')->once()->andReturn($isValid);
        });
    }
}
