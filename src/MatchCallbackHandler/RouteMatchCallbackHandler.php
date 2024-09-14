<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\Routing\RouterInterface;
use function sprintf;

final class RouteMatchCallbackHandler implements MatchCallbackHandlerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly RouterInterface $router
    ) {
        $this->logger = new NullLogger();
    }

    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'route:');
    }

    public function handle(string $matchCallback): string
    {
        $routeName = trim(mb_substr($matchCallback, 6));
        $route = $this->router->generate($routeName);
        $this->logger->debug('Route match callback found.', [
            'match_callback' => $matchCallback,
            'route' => $route,
        ]);

        return sprintf("({url}) => url.pathname === '%s'", $route);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
