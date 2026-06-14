<?php

namespace App\Core;

class Router
{
    private $routes = [];

    public function get(string $path, array $handler, bool $auth = false): void
    {
        $this->add('GET', $path, $handler, $auth);
    }

    public function post(string $path, array $handler, bool $auth = false): void
    {
        $this->add('POST', $path, $handler, $auth);
    }

    private function add(string $method, string $path, array $handler, bool $auth): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'regex'   => $this->compile($path),
            'handler' => $handler,
            'auth'    => $auth,
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        // Strip the base path prefix so routes are always relative to app root
        $base = Url::base();
        $path = $request->path;
        if ($base !== '' && strpos($path, $base) === 0) {
            $stripped = substr($path, strlen($base));
            $path = ($stripped === '' || $stripped === false) ? '/' : $stripped;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['auth']) {
                Auth::requireAuth();
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            [$class, $method] = $route['handler'];
            $controller = new $class();
            $controller->$method($params);
            return;
        }

        Response::abort(404);
    }
}
