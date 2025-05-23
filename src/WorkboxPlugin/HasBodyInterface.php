<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

interface HasBodyInterface
{
    public function renderBody(string $pluginId, int $jsonOptions = 0): string;
}
