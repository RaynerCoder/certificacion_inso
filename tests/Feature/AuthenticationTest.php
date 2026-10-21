<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Permiso;
use App\Models\Role;
use App\Services\LoginCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Código de seguridad');
        $this->assertFalse(Route::has('captcha.login'));
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

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();
        $this->darAccesoPanel($user);

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
        $this->darAccesoPanel($user);

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

    public function test_cuenta_sin_permisos_permanece_en_login(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors([
            'email' => \App\Http\Middleware\VerificarAccesoSistema::MENSAJE,
        ]);
        $this->assertGuest();
    }

    public function test_sesion_sin_permisos_se_cierra_desde_raiz_menu_y_login(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        foreach (['/', '/menu', '/login'] as $url) {
            $this->actingAs($user)->get($url)
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');
            $this->app['auth']->forgetGuards();
            $this->assertGuest();
        }
        $this->get('/login')->assertOk();
    }

    public function test_sin_permiso_de_una_seccion_conserva_sesion_y_devuelve_403(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->darAccesoPanel($user);
        $this->actingAs($user)->get(route('usuarios_index'))->assertForbidden();
        $this->assertAuthenticatedAs($user);
    }

    public function test_superusuario_sin_permisos_explicitos_conserva_acceso(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $rol = Role::create(['slug' => 'super-administrador', 'name' => 'Super Administrador', 'estado' => 1]);
        $user->roles()->attach($rol);
        $this->actingAs($user)->get('/')->assertRedirect('/menu');
        $this->assertAuthenticatedAs($user);
    }

    private function darAccesoPanel(User $user): void
    {
        $permiso = Permiso::firstOrCreate(['nombre' => 'dashboard.ver'], ['estado' => 1]);
        $user->permisosDirectos()->syncWithoutDetaching([$permiso->id]);
    }
}
