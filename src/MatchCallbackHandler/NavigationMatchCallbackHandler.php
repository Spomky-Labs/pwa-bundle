<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;

final class NavigationMatchCallbackHandler implements MatchCallbackHandlerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function supports(string $matchCallback): bool
    {
        return $matchCallback === 'navigate';
    }

    public function handle(string $matchCallback): string
    {
        $this->logger->debug('Navigation match callback found.', [
            'match_callback' => $matchCallback,
        ]);

        return "({request}) => request.mode === 'navigate'";
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
