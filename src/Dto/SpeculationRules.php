<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class SpeculationRules
{
    public bool $enabled = false;

    /**
     * Rules for prefetching (download resources but don't execute).
     * @var array<SpeculationRule>
     */
    #[SerializedName('prefetch')]
    public array $prefetch = [];

    /**
     * Rules for prerendering (download and execute in hidden context).
     * @var array<SpeculationRule>
     */
    #[SerializedName('prerender')]
    public array $prerender = [];
}
