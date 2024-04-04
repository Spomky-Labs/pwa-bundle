<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\MatchCallbackHandler;

interface MatchCallbackHandlerInterface
{
    public function supports(string $matchCallback): bool;

    public function handle(string $matchCallback): string;
}
