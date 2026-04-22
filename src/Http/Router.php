<?php

declare(strict_types=1);

namespace App\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct(callable $routeDefinition)
    {
        $this->dispatcher = simpleDispatcher($routeDefinition);
    }

    public function dispatch(Request $request): RouteMatch
    {
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getPath()
        );

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => RouteMatch::notFound(),
            Dispatcher::METHOD_NOT_ALLOWED => RouteMatch::methodNotAllowed(),
            Dispatcher::FOUND => RouteMatch::found(
                handler: $routeInfo[1],
                params: $routeInfo[2]
            ),
        };
    }
}

class RouteMatch
{
    private string $status;
    private mixed $handler = null;
    private array $params = [];

    private function __construct(string $status)
    {
        $this->status = $status;
    }

    public static function notFound(): self
    {
        return new self('NOT_FOUND');
    }

    public static function methodNotAllowed(): self
    {
        return new self('METHOD_NOT_ALLOWED');
    }

    public static function found(mixed $handler, array $params = []): self
    {
        $match = new self('FOUND');
        $match->handler = $handler;
        $match->params = $params;
        return $match;
    }

    public function isFound(): bool
    {
        return $this->status === 'FOUND';
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
