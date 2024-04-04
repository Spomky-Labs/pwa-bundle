<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

final readonly class OriginMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'origin:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("({url}) => url.origin === '%s'", trim(mb_substr($matchCallback, 7)));
    }
}
