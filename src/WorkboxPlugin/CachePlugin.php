<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

interface CachePlugin
{
    public function getName(): string;

    public function render(int $jsonOptions = 0): string;
}
