<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

final readonly class ExactPathnameMatchCallbackHandler implements MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'pathname:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("({url}) => url.pathname === '%s'", trim(mb_substr($matchCallback, 9)));
    }
}
