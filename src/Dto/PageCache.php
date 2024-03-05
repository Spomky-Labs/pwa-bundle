<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PageCache
{
    public bool $enabled = true;

    #[SerializedName('cache_name')]
    public string $cacheName = 'pages';

    #[SerializedName('regex')]
    public string $regex = '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/';

    #[SerializedName('network_timeout')]
    public int $networkTimeout = 3;

    /**
     * @var array<Url>
     */
    public array $urls = [];
}
