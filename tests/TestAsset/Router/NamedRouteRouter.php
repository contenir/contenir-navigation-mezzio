<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Router;

use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A router that knows only the address of each route it was given by name, as a test's routes
 */
final readonly class NamedRouteRouter implements RouterInterface
{
    /**
     * @param array<string, string> $paths Each route's address, by its name
     */
    public function __construct(
        private array $paths,
    ) {}

    #[Override]
    public function addRoute(Route $route): void {}

    /**
     * @throws RuntimeException When no route has the name
     */
    #[Override]
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        return $this->paths[$name] ?? throw new RuntimeException("No route is named {$name}");
    }

    #[Override]
    public function match(ServerRequestInterface $request): RouteResult
    {
        return RouteResult::fromRouteFailure(null);
    }
}
