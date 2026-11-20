<?php
declare(strict_types=1);

namespace App\Core;

/** Tiny regex router: {id} placeholders become numeric route params. */
final class Router
{
    /** @var array<int, array{string,string,callable}> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        foreach ($this->routes as [$m, $pattern, $handler]) {
            if ($m !== $method) {
                continue;
            }
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                $handler(...array_values($params));
                return;
            }
        }

        if (str_starts_with($path, '/api/')) {
            Response::json(['error' => 'Not found.'], 404);
        }
        http_response_code(404);
        echo 'Page not found.';
    }
}
