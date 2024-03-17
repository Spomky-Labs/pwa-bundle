<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\MatchCallbackHandler;

final readonly class RegexMatchCallbackHandler implements MatchCallbackHandler
{
    public function supports(string $matchCallback): bool
    {
        return str_starts_with($matchCallback, 'regex:');
    }

    public function handle(string $matchCallback): string
    {
        return sprintf("new RegExp('%s')", trim(mb_substr($matchCallback, 6)));
    }
}
