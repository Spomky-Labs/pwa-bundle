<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

final readonly class PathnameStartsWithMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'startsWith:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("({url}) => url.pathname.startsWith('%s')", trim(mb_substr($matchCallback, 11)));
    }
}
