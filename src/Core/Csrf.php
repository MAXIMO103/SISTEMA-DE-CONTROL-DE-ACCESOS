<?php
namespace App\Core;

class Csrf
{
    public static function getToken(): string
    {
        Session::start();
        $token = Session::get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $saved = Session::get('csrf_token');
        if (!$saved || !$token) {
            return false;
        }
        return hash_equals($saved, $token);
    }

    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

