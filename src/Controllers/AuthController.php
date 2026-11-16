<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class AuthController
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        View::render('auth.login', [
            'title' => 'Acceso al Sistema - COEDE',
            'error' => Session::getFlash('error')
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Por favor complete todos los campos de autenticación.');
            Response::redirect('/login');
        }

        $result = Auth::attempt($username, $password, $request);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            Response::redirect('/login');
        }

        Response::redirect('/');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/login');
    }

    /**
     * Endpoint AJAX para comprobar tiempo restante de sesión y mantener viva la sesión
     */
    public function checkSession(Request $request): void
    {
        Session::start();
        $userId = Session::get('user_id');

        if (!$userId || !Auth::check()) {
            Response::json(['authenticated' => false, 'remaining_seconds' => 0]);
        }

        $last = (int)Session::get('last_activity', time());
        $lifetime = (int)($_ENV['SESSION_LIFETIME_MINUTES'] ?? 15) * 60;
        $remaining = max(0, $lifetime - (time() - $last));

        Response::json([
            'authenticated'     => true,
            'remaining_seconds' => $remaining,
            'warning_threshold' => 60 // Avisar al quedar 1 minuto (a los 14 minutos)
        ]);
    }

    public function pingSession(Request $request): void
    {
        Session::start();
        if (Auth::check()) {
            Session::set('last_activity', time());
            Response::json(['success' => true]);
        } else {
            Response::json(['success' => false], 401);
        }
    }
}

