<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array|string $roles)
    {
        $this->allowedRoles = is_array($roles) ? $roles : [$roles];
    }

    public function handle(Request $request): bool
    {
        $currentRole = Auth::role();

        if (!$currentRole || !in_array($currentRole, $this->allowedRoles, true)) {
            if ($request->isAjax()) {
                Response::json([
                    'error' => 'Acceso denegado: Su rol no posee privilegios suficientes para ejecutar esta operación.'
                ], 403);
            }
            
            http_response_code(403);
            View::render('errors.403', [
                'title' => 'Acceso Restringido - COEDE',
                'message' => 'No tiene autorización para acceder a este módulo institucional.'
            ], 'main');
            return false;
        }

        return true;
    }
}

