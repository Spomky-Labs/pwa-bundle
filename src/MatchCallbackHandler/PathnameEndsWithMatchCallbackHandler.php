<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use function sprintf;

final class PathnameEndsWithMatchCallbackHandler implements MatchCallbackHandlerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'endsWith:');
    }

    public function handle(string $matchCallback): string
    {
        $this->logger->debug('Pathname ends with match callback found.', [
            'match_callback' => $matchCallback,
        ]);

        return sprintf("({url}) => url.pathname.endsWith('%s')", trim(mb_substr($matchCallback, 9)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
