<?php
namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware
{
    public function handle(Request $request): bool
    {
        if ($request->isPost()) {
            $token = $request->input('csrf_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!Csrf::validate($token)) {
                if ($request->isAjax()) {
                    Response::json(['error' => 'Token de seguridad inválido o expirado. Recargue la página.'], 403);
                }
                Session::flash('error', 'Token de seguridad inválido o expirado. Por favor intente de nuevo.');
                Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
                return false;
            }
        }
        return true;
    }
}

