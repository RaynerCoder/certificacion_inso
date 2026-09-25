<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenBCBService;

class OpenBCBController extends Controller
{
    public function probar(OpenBCBService $openBCB)
    {
        dd('hola mundo');

    }
}
