<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PageCache
{
    #[SerializedName('cache_name')]
    public string $cacheName;

    #[SerializedName('regex')]
    public string $regex;

    #[SerializedName('network_timeout')]
    public int $networkTimeout = 3;

    public string $strategy = 'networkFirst';

    public bool $broadcast = false;

    /**
     * @var array<string>
     */
    #[SerializedName('broadcast_headers')]
    public array $broadcastHeaders = ['Content-Type', 'ETag', 'Last-Modified'];

    /**
     * @var array<Url>
     */
    public array $urls = [];
}
