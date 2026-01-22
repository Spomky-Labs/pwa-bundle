<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ResourceHints
{
    public bool $enabled = false;

    /**
     * Whether to automatically add preconnect hints for detected external origins.
     */
    #[SerializedName('auto_preconnect')]
    public bool $autoPreconnect = true;

    /**
     * Additional origins to preconnect to.
     * @var array<string>
     */
    #[SerializedName('preconnect')]
    public array $preconnect = [];

    /**
     * Additional origins for DNS prefetch only.
     * @var array<string>
     */
    #[SerializedName('dns_prefetch')]
    public array $dnsPrefetch = [];

    /**
     * Resources to preload.
     * @var array<PreloadResource>
     */
    #[SerializedName('preload')]
    public array $preload = [];
}
