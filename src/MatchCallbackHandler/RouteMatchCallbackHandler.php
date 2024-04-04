<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Symfony\Component\Routing\RouterInterface;

final readonly class RouteMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'route:');
    }

    public function handle(string $matchCallback): string
    {
        $routeName = trim(mb_substr($matchCallback, 6));
        $route = $this->router->generate($routeName);

        return sprintf("({url}) => url.pathname === '%s'", $route);
    }
}
