<?php

use Illuminate\Routing\Route;

uses(Tests\TestCase::class);

function apiRoutesForContractTest(): array
{
    return array_values(array_filter(
        app('router')->getRoutes()->getRoutes(),
        fn (Route $route) => $route->uri() === 'api' || str_starts_with($route->uri(), 'api/')
    ));
}

it('registers every API route with a public callable controller action', function () {
    $routes = apiRoutesForContractTest();
    expect($routes)->not->toBeEmpty();

    $invalidActions = [];

    foreach ($routes as $route) {
        $action = $route->getAction('uses');
        if ($action instanceof Closure) {
            continue;
        }

        if (!is_string($action) || $action === '') {
            $invalidActions[] = implode('|', $route->methods()) . ' ' . $route->uri() . ': missing action';
            continue;
        }

        [$controller, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');
        if (!class_exists($controller) || !method_exists($controller, $method)) {
            $invalidActions[] = implode('|', $route->methods()) . ' ' . $route->uri() . ": {$action} does not exist";
            continue;
        }

        if (!(new ReflectionMethod($controller, $method))->isPublic()) {
            $invalidActions[] = implode('|', $route->methods()) . ' ' . $route->uri() . ": {$action} is not public";
        }
    }

    expect($invalidActions)->toBe([], implode(PHP_EOL, $invalidActions));
});

it('uses registered middleware on every API route', function () {
    $router = app('router');
    $aliases = $router->getMiddleware();
    $groups = $router->getMiddlewareGroups();
    $unknownMiddleware = [];

    foreach (apiRoutesForContractTest() as $route) {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            $name = explode(':', $middleware, 2)[0];
            if (isset($aliases[$name]) || isset($groups[$name]) || class_exists($name)) {
                continue;
            }

            $unknownMiddleware[] = implode('|', $route->methods()) . ' ' . $route->uri() . ": {$middleware}";
        }
    }

    expect($unknownMiddleware)->toBe([], implode(PHP_EOL, $unknownMiddleware));
});
