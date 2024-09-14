<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use function sprintf;

final class DestinationMatchCallbackHandler implements MatchCallbackHandlerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'destination:');
    }

    public function handle(string $matchCallback): string
    {
        $this->logger->debug('Destination match callback found.', [
            'match_callback' => $matchCallback,
        ]);

        return sprintf("({request}) => request.destination === '%s'", trim(mb_substr($matchCallback, 12)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
