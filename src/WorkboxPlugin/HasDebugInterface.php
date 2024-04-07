<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

interface HasDebugInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getDebug(): array;
}
