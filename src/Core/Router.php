<?php
namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): void
    {
        $reqMethod = $request->getMethod();
        $reqUri = $request->getUri();
        if ($reqUri !== '/' && str_ends_with($reqUri, '/')) {
            $reqUri = rtrim($reqUri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $reqUri, $matches)) {
                // Extraer parámetros nominales
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                // Ejecutar Middlewares
                foreach ($route['middlewares'] as $mw) {
                    $instance = match($mw) {
                        'auth'    => new AuthMiddleware(),
                        'admin'   => new RoleMiddleware('ADMINISTRADOR'),
                        'service' => new RoleMiddleware(['ADMINISTRADOR', 'SERVICIO']),
                        'csrf'    => new CsrfMiddleware(),
                        default   => is_object($mw) ? $mw : null
                    };

                    if ($instance && method_exists($instance, 'handle')) {
                        if ($instance->handle($request) === false) {
                            return; // Middleware bloqueó la petición
                        }
                    }
                }

                // Despachar al Handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func_array($handler, array_merge([$request], $params));
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $method], array_merge([$request], $params));
                    return;
                }
            }
        }

        // Si ninguna ruta coincide: 404
        http_response_code(404);
        if ($request->isAjax()) {
            Response::json(['error' => 'Recurso no encontrado (404)'], 404);
        }
        View::render('errors.404', [
            'title' => 'Página no encontrada - COEDE',
            'requestedUri' => $reqUri
        ], 'main');
    }
}

