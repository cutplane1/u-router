<?php

namespace Cutplane1;

/**
 * Routing Class.
 */
class URouter
{
    /**
     * Error callback.
     */
    public $error_callback;

    /**
     * Request URI.
     */
    public string $default_req_uri;

    /**
     * Invokes a callback.
     */
    public bool $is_found = false;

    /**
     * Array of routes.
     */
    public array $routes = [];

    /**
     * Array of middlewares.
     */
    public array $middlewares = [];

    public function __construct()
    {
        if ('cli' === php_sapi_name()) {
            // $_SERVER['REQUEST_METHOD'] = "GET";
            $this->default_req_uri = '/';
        } else {
            $this->default_req_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
    }

    public function any(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "ANY");

        return $this;
    }

    /**
     * GET HTTP Verb.
     */
    public function get(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "GET");

        return $this;
    }
    /**
     * POST HTTP Verb.
     */
    public function post(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "POST");

        return $this;
    }
    /**
     * PUT HTTP Verb.
     */
    public function put(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "PUT");

        return $this;
    }
    /**
     * PATCH HTTP Verb.
     */
    public function patch(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "PATCH");

        return $this;
    }
    /**
     * DELETE HTTP Verb.
     */
    public function delete(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "DELETE");

        return $this;
    }
    /**
     * OPTIONS HTTP Verb.
     */
    public function options(string $rule, callable $callback, mixed $middleware = null): self
    {
        $this->route($rule, $callback, $middleware, "OPTIONS");

        return $this;
    }

    /**
     * Adds route to array.
     */
    public function route(string $rule, callable $callback, mixed $middleware = null, string $verb): self
    {
        $pattern = $this->rule2regex($rule);
        array_push($this->routes, ['pattern' => $pattern, 'callback' => $callback, 'rule' => $rule, 'middleware' => $middleware, 'verb' => $verb]);

        return $this;
    }
    /**
     * Adds a route group to an array.
     */
    public function group(array $routes, callable $middleware = null): self
    {
        foreach ($routes as $rule => $callback) {
            $pattern = $this->rule2regex($rule);
            array_push($this->routes, ['pattern' => $pattern, 'callback' => $callback, 'rule' => $rule, 'middleware' => $middleware]);
        }

        return $this;
    }

    /**
     * Turns an easy-to-read rule into a regular expression.
     */
    public function rule2regex(string $rule): string
    {
        // $rule = str_replace('/', "\/", $rule);
        $rule = str_replace(["<any>", "<str>", "<string>", "<#>"], "(\w+)", $rule);
        $rule = str_replace(["<int>", "<integer>", "<@>"], "(\d+)", $rule);

        return '@^'.$rule.'$@';
    }

    /**
     * Adds middleware to array.
     */
    public function middleware(callable $callback): self
    {
        array_push($this->middlewares, $callback);

        return $this;
    }

    /**
     * Executes callback on error.
     */
    public function handle_error()//: mixed
    {
        if ($this->error_callback) {
            call_user_func($this->error_callback);
        } else {
            http_response_code(404);
            echo "404";
        }
    }

    /**
     * Sets error callback.
     */
    public function not_found(callable $callback): self
    {
        $this->error_callback = $callback;

        return $this;
    }

    /**
     * Executes a callback when a route is found.
     */
    public function dispatch(string $uri = null, string $method = null): mixed
    {
        if (!$uri) {
            $uri = $this->default_req_uri;
        }

        if (!$method) {
            if ('cli' === php_sapi_name()) {
                $method = "GET";
            } else {
                $method = $_SERVER['REQUEST_METHOD'];
            }
            
            
        }

        foreach ($this->routes as $route) {
            $match = preg_match($route['pattern'], $uri, $out);
            array_shift($out);
            if ($match and $route['verb'] === $method or $match and $route['verb'] === "ANY") {
                $this->call_middlewares();

                if ($route['middleware']) {
                    call_user_func($route['middleware']);
                }
                return call_user_func_array($route['callback'], $out);
                $this->is_found = true;
            }
        }

        if (!$this->is_found) {
            $this->handle_error();
        }
    }

    public function call_middlewares()
    {
        foreach ($this->middlewares as $middleware) {
            call_user_func($middleware);
        }
    }

    /**
     * Returns true if route is found.
     */
    public function test(string $uri, string $method = "GET"): bool
    {
        foreach ($this->routes as $route) {
            $match = preg_match($route['pattern'], $uri, $out);
            if ($match and $route['verb'] === $method or $match and $route['verb'] === "ANY") {
                return true;
            }
        }
        return false;
    }
}
