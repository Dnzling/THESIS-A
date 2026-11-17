<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;

uses(Tests\TestCase::class);

function hrApiRoutesForSmokeTest(): array
{
    return array_values(array_filter(
        app('router')->getRoutes()->getRoutes(),
        fn (Route $route) => str_starts_with($route->uri(), 'api/')
            && str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\Api\\Hr\\')
    ));
}

function hrApiRouteSampleForSmokeTest(Route $route): array
{
    $method = in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];
    $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());

    return [$method, '/' . $uri];
}

beforeEach(function () {
    // Any accidental database query must fail before it can touch the configured database.
    config()->set('database.default', 'hr_smoke_no_database');
});

it('registers every HR API route with a public controller method and known middleware', function () {
    $routes = hrApiRoutesForSmokeTest();
    expect(count($routes))->toBeGreaterThan(0);

    $router = app('router');
    $aliases = $router->getMiddleware();
    $groups = $router->getMiddlewareGroups();
    $failures = [];

    foreach ($routes as $route) {
        $action = $route->getActionName();
        [$controller, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');

        if (!class_exists($controller) || !method_exists($controller, $method)) {
            $failures[] = implode('|', $route->methods()) . ' /' . $route->uri() . ": {$action} does not exist";
        } elseif (!(new ReflectionMethod($controller, $method))->isPublic()) {
            $failures[] = implode('|', $route->methods()) . ' /' . $route->uri() . ": {$action} is not public";
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }
            $name = explode(':', $middleware, 2)[0];
            if (!isset($aliases[$name]) && !isset($groups[$name]) && !class_exists($name)) {
                $failures[] = implode('|', $route->methods()) . ' /' . $route->uri() . ": unknown middleware {$middleware}";
            }
        }
    }

    expect($failures)->toBe([], implode(PHP_EOL, $failures));
});

it('matches every HR API route to its intended handler', function () {
    $failures = [];

    foreach (hrApiRoutesForSmokeTest() as $route) {
        [$method, $uri] = hrApiRouteSampleForSmokeTest($route);
        try {
            $matched = app('router')->getRoutes()->match(Request::create($uri, $method));
            if ($matched !== $route) {
                $failures[] = "{$method} {$uri} resolves to {$matched->getActionName()} instead of {$route->getActionName()}";
            }
        } catch (Throwable $exception) {
            $failures[] = "{$method} {$uri}: {$exception->getMessage()}";
        }
    }

    expect($failures)->toBe([], implode(PHP_EOL, $failures));
});

it('rejects guests on every protected HR API route without querying a database', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $failures = [];
    $protectedRoutes = 0;

    foreach (hrApiRoutesForSmokeTest() as $route) {
        $requiresAuth = collect($route->gatherMiddleware())->contains(
            fn ($middleware) => is_string($middleware)
                && ($middleware === 'auth' || str_starts_with($middleware, 'auth:'))
        );
        if (!$requiresAuth) {
            continue;
        }

        $protectedRoutes++;
        [$method, $uri] = hrApiRouteSampleForSmokeTest($route);
        $response = $this->json($method, $uri);
        if ($response->getStatusCode() !== 401) {
            $failures[] = "{$method} {$uri}: expected 401, got {$response->getStatusCode()}";
        }
    }

    expect($protectedRoutes)->toBeGreaterThan(0)
        ->and($failures)->toBe([], implode(PHP_EOL, $failures));
});
