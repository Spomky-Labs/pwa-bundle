<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use function sprintf;

final class OriginMatchCallbackHandler implements MatchCallbackHandlerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'origin:');
    }

    public function handle(string $matchCallback): string
    {
        $this->logger->debug('Origin match callback found.', [
            'match_callback' => $matchCallback,
        ]);

        return sprintf("({url}) => url.origin === '%s'", trim(mb_substr($matchCallback, 7)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
