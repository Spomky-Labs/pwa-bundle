<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\MatchCallbackHandler;

final readonly class NavigationMatchCallbackHandler implements MatchCallbackHandler
{
    public function supports(string $matchCallback): bool
    {
        return $matchCallback === 'navigate';
    }

    public function handle(string $matchCallback): string
    {
        return "({request}) => request.mode === 'navigate'";
    }
}
