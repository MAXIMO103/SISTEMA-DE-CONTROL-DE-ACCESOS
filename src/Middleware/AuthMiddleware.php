<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::json(['error' => 'No autenticado o sesión expirada.'], 401);
            }
            Session::flash('error', 'Debe iniciar sesión para acceder al sistema.');
            Response::redirect('/login');
            return false;
        }
        return true;
    }
}

