<?php

namespace App;

use App\HttpException;
use App\Middleware\AuthMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\SystemAdminMiddleware;

class Router
{
    private array $routes = [];

    public function get(
        string $path,
        callable|array $handler,
        array $middleware = []
    ): void {
        $this->addRoute(
            'GET',
            $path,
            $handler,
            $middleware
        );
    }

    public function post(
        string $path,
        callable|array $handler,
        array $middleware = []
    ): void {
        $this->addRoute(
            'POST',
            $path,
            $handler,
            $middleware
        );
    }

    private function addRoute(
        string $method,
        string $path,
        callable|array $handler,
        array $middleware
    ): void {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(
        string $method,
        string $path
    ): mixed {

        $route = null;
        $parameters = [];

        /*
         * First try an exact route match.
         */
        if (isset($this->routes[$method][$path])) {

            $route = $this->routes[$method][$path];

        } else {

            /*
             * Check routes containing parameters.
             *
             * Example:
             * /employees/{id}
             *
             * matches:
             * /employees/1
             * /employees/25
             */
            foreach ($this->routes[$method] ?? [] as $routePath => $registeredRoute) {

                $pattern = preg_replace(
                    '/\{([^}]+)\}/',
                    '([^/]+)',
                    $routePath
                );

                $pattern = '#^' . $pattern . '$#';

                if (preg_match(
                    $pattern,
                    $path,
                    $matches
                )) {

                    array_shift($matches);

                    preg_match_all(
                        '/\{([^}]+)\}/',
                        $routePath,
                        $parameterNames
                    );

                    foreach (
                        $parameterNames[1] as $index => $name
                    ) {
                        $parameters[$name] =
                            $matches[$index] ?? null;
                    }

                    $route = $registeredRoute;

                    break;
                }
            }
        }

        /*
         * Route not found.
         */
        if ($route === null) {
            throw new HttpException(
                'The page you requested could not be found.',
                404
            );
        }

        /*
         * Run route middleware.
         */
        foreach ($route['middleware'] as $middleware) {

            if (is_string($middleware)) {

                $this->runMiddleware($middleware);
            }
        }

        /*
         * Run controller/action.
         */
        $handler = $route['handler'];

        if (is_array($handler)) {

            [$controller, $action] = $handler;

            $controllerInstance = new $controller();

            return $controllerInstance->$action(
                ...array_values($parameters)
            );
        }

        return call_user_func($handler);
    }

    private function runMiddleware(
        string $middleware
    ): void {

        switch ($middleware) {

            case 'auth':

                AuthMiddleware::handle();

                break;

            case 'tenant':

                TenantMiddleware::handle();

                break;

            case 'system_admin':

                SystemAdminMiddleware::handle();

                break;

            default:

                if (
                    str_starts_with(
                        $middleware,
                        'permission:'
                    )
                ) {

                    $permission = substr(
                        $middleware,
                        strlen('permission:')
                    );

                    PermissionMiddleware::handle(
                        $permission
                    );

                    break;
                }

                throw new \LogicException(
                    "Unknown middleware: {$middleware}"
                );
        }
    }
}