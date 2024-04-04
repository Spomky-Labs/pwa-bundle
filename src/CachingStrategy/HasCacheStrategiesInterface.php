<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

interface HasCacheStrategiesInterface
{
    /**
     * @return array<CacheStrategyInterface>
     */
    public function getCacheStrategies(): array;
}
