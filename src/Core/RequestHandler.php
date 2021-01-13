<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class RequestHandler
{
    private function cors()
    {

        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }
    }

    public function handle(string $url, string $method): void
    {
        $this->cors();

        try {
            $container = new Container();
            $router = new Router();
            $result = $router->getController($url, $method);

            if ($result === null) {
                if (constant('ALLOW_NON_EXISTING_LINKS') === true) {
                    http_response_code(404);
                    die;
                }

                header("Access-Control-Allow-Origin: *");
                http_response_code(500);
                $result = [
                    'error' => 'no_route',
                    'url' => $url
                ];
                echo json_encode($result, JSON_PRETTY_PRINT);
                die;
            }

            $indexController = $container->get($result['controller']);

            if (method_exists($indexController, 'setRoutePattern')) {
                call_user_func_array([$indexController, 'setRoutePattern'], [$result['routePattern']]);
            }

            $routeParameters = $result['parameters'];

            $reflectionMethod = new \ReflectionMethod(get_class($indexController), 'index');
            $methodParams = $reflectionMethod->getParameters();
            $index = 0;
            foreach ($methodParams as $methodParam) {
                if ($methodParam->getType() instanceof \ReflectionNamedType) {
                    $parameterType = $methodParam->getType()->getName();
                    if ($parameterType === 'int' && $index < count($routeParameters) - 1) {
                        $routeParameters[$index] = intval($routeParameters[$index]);
                    }
                }
                $index++;
            }

            /** @var MagicResponse $response */
            $response = call_user_func_array([$indexController, 'index'], $routeParameters);
            $this->processResult($response);
        } catch (ApiException $apiException) {
            $response = new MagicResponse($apiException->generateJson(), $apiException->getStatusCode());
            $this->processResult($response);
        }
    }

    private function generateJsonFromException(\Throwable $e): array
    {
        return [
            'error' => 'internal_error',
            'exception' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace()
            ]
        ];
    }

    private function processResult(MagicResponse $response): void
    {
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: ' . $response->getContentType());
        http_response_code($response->getHttpCode());
        echo $response->getContent();
    }
}
