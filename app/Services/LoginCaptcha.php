<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;

class LoginCaptcha
{
    public const SESSION_KEY = 'auth.login_captcha';

    public function __construct(private readonly LoginCaptchaImage $image) {}

    public function issue(Session $session): string
    {
        $code = $this->generateCode();

        // La sesion guarda una firma del codigo, nunca el texto que ve el usuario.
        $session->put(self::SESSION_KEY, [
            'hash' => $this->hash($code),
            'expires_at' => now()->addSeconds($this->lifetime())->timestamp,
        ]);

        return $this->image->render($code);
    }

    public function verify(Session $session, ?string $answer): bool
    {
        // pull hace que cada imagen sea valida para un solo envio del formulario.
        $challenge = $session->pull(self::SESSION_KEY);
        $answer = mb_strtoupper(trim((string) $answer));
        $length = (int) config('login_captcha.length', 6);
        $validFormat = preg_match("/^[A-Z2-9]{{$length}}$/", $answer) === 1;

        if (! is_array($challenge)
            || empty($challenge['hash'])
            || empty($challenge['expires_at'])
            || (int) $challenge['expires_at'] < now()->timestamp
            || ! $validFormat) {
            return false;
        }

        return hash_equals((string) $challenge['hash'], $this->hash($answer));
    }

    private function generateCode(): string
    {
        $characters = (string) config('login_captcha.characters');
        $length = (int) config('login_captcha.length', 6);
        $lastIndex = strlen($characters) - 1;
        $code = '';

        for ($position = 0; $position < $length; $position++) {
            $code .= $characters[random_int(0, $lastIndex)];
        }

        return $code;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function lifetime(): int
    {
        return max(60, (int) config('login_captcha.expires_in_seconds', 600));
    }
}
