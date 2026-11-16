<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;

class HomeController
{
    public function index(Request $request): void
    {
        $user = Auth::user();

        // Tarjetas y módulos institucionales disponibles según el rol
        View::render('home.index', [
            'title' => 'Portal Principal - COEDE',
            'user' => $user,
            'isAdmin' => Auth::isAdmin(),
            'isService' => Auth::isService()
        ], 'main');
    }
}

