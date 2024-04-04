<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

final readonly class DestinationMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'destination:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("({request}) => request.destination === '%s'", trim(mb_substr($matchCallback, 12)));
    }
}
