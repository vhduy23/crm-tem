<?php
namespace Core;

class Router {
    protected $routes = [];

    // Add a route (Basic implementation)
    public function add($method, $route, $controllerAction) {
        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'action' => $controllerAction
        ];
    }

    // Dispatch the request
    public function dispatch($url) {
        $url = trim($url, '/');
        if (empty($url)) {
            $url = '/'; // Default to home
        }
        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                // Convert {param} to regex
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['route']);
                $pattern = "@^" . $pattern . "$@D";
                
                $routeToMatch = $url === '/' ? '/' : '/' . $url;

                if (preg_match($pattern, $routeToMatch, $matches)) {
                    array_shift($matches); // Remove full match

                    list($controllerName, $action) = explode('@', $route['action']);
                    $controllerClass = "App\\Controllers\\" . $controllerName;
                    
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $action)) {
                            // Call the action with parameters
                            call_user_func_array([$controller, $action], $matches);
                            return true;
                        }
                    }
                }
            }
        }
        
        // If no route matches, we can return false, so index.php knows it wasn't handled by MVC.
        http_response_code(404);
        echo "404 Not Found (MVC Router)";
        return false;
    }
}
