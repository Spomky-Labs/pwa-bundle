<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PreloadResource
{
    /**
     * The URL or path to preload.
     */
    public string $href = '';

    /**
     * The 'as' attribute value: script, style, font, image, fetch, etc.
     */
    public string $as = '';

    /**
     * The MIME type of the resource.
     */
    public ?string $type = null;

    /**
     * Whether to use crossorigin attribute.
     */
    public ?string $crossorigin = null;

    /**
     * The fetch priority: high, low, or auto.
     */
    #[SerializedName('fetchpriority')]
    public ?string $fetchPriority = null;

    /**
     * Media query for responsive preloading.
     */
    public ?string $media = null;
}
