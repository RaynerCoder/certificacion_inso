<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\LoginCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    private const CAPTCHA_VERIFIED = 'login_captcha_verified';

    public function __construct(private readonly LoginCaptcha $captcha) {}

    public function __invoke(Request $request): ?User
    {
        // CAPTCHA deshabilitado temporalmente para permitir el inicio de sesión directo.
        // Para reactivarlo, quite el comentario de la siguiente línea.
        // $this->validateCaptcha($request);

        $access = (string) $request->input(Fortify::username());
        $user = User::query()
            ->where('email', $access)
            ->orWhere('name', $access)
            ->first();

        if (! $user || (string) $user->estado !== '1') {
            return null;
        }

        return Hash::check((string) $request->password, $user->password) ? $user : null;
    }

    private function validateCaptcha(Request $request): void
    {
        // Fortify consulta dos veces las credenciales cuando revisa el segundo factor.
        // Esta marca vive solo durante la solicitud y evita consumir dos veces el mismo código.
        if ($request->attributes->get(self::CAPTCHA_VERIFIED) === true) {
            return;
        }

        $length = (int) config('login_captcha.length', 6);

        $request->validate([
            'captcha' => ['required', 'string', "size:{$length}"],
        ], [
            'captcha.required' => 'Escriba el código de seguridad.',
            'captcha.size' => 'El código de seguridad debe tener :size caracteres.',
        ]);

        if (! $this->captcha->verify($request->session(), $request->input('captcha'))) {
            throw ValidationException::withMessages([
                'captcha' => 'El código de seguridad es incorrecto o venció. Genere uno nuevo.',
            ]);
        }

        $request->attributes->set(self::CAPTCHA_VERIFIED, true);
    }
}
