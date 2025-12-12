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

        $pathname = trim(mb_substr($matchCallback, 9));

        return sprintf(
            <<<'JS'
({request, url}) => {
    if (!url.pathname.endsWith('%s')) {
        return false;
    }
    const acceptHeader = request.headers.get('Accept') || '';
    if (acceptHeader.includes('text/vnd.turbo-stream.html')) {
        return false;
    }
    if (request.headers.get('Turbo-Frame')) {
        return false;
    }

    return true;
}
JS
            ,
            $pathname
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
