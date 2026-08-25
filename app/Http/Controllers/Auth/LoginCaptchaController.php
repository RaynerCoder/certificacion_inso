<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginCaptcha;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginCaptchaController extends Controller
{
    public function __invoke(Request $request, LoginCaptcha $captcha): Response
    {
        $image = $captcha->issue($request->session());

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
