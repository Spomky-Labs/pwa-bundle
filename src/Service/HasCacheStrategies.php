<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

interface HasCacheStrategies
{
    /**
     * @return array<CacheStrategy>
     */
    public function getCacheStrategies(): array;
}
