<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class Router
{
    /** @var string[][] */
    private array $routes;

    public function __construct()
    {
        include constant('BASE_PATH') . '/config/routes.php';

        $this->routes = getRoutes();
    }

    public function getController(string $url, string $method): ?array
    {
        $urlParts = parse_url($url);
        $urlPath = rtrim($urlParts['path'], '/');

        foreach ($this->routes as $route) {
            $routeUrlPattern = $route[0];
            $routeUrlPattern = str_replace('/', '\/', $routeUrlPattern);
            $routeUrlPattern = preg_replace('/\{[a-zA-Z]+\}/', '([a-zA-Z0-9_\.]+)', $routeUrlPattern);

            $routeMethod = $route[1];
            $output = [];

            preg_match('/' . $routeUrlPattern . '$/', $urlPath, $output);
            if (count($output) === 0) {
                continue;
            }

            if ($routeMethod === $method) {
                array_shift($output);
                return [
                    'routePattern' => $route[0],
                    'controller' => $route[2],
                    'parameters' => $output
                ];
            }
        }

        return null;
    }
}