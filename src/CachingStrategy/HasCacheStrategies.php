<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

interface HasCacheStrategies
{
    /**
     * @return array<CacheStrategy>
     */
    public function getCacheStrategies(): array;
}
