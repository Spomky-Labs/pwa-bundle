<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

final readonly class PathnameEndsWithMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'endsWith:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("({url}) => url.pathname.endsWith('%s')", trim(mb_substr($matchCallback, 9)));
    }
}
