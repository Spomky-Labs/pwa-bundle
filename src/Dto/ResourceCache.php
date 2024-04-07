<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @final
 */
class ResourceCache extends Cache
{
    #[SerializedName('match_callback')]
    public string $matchCallback;

    #[SerializedName('network_timeout')]
    public int $networkTimeout = 3;

    public string $strategy = 'NetworkFirst';

    public bool $broadcast = false;

    #[SerializedName('range_requests')]
    public bool $rangeRequests = false;

    /**
     * @var int[]
     */
    #[SerializedName('cacheable_response_statuses')]
    public array $cacheableResponseStatuses = [0, 200];

    /**
     * @var array<string, string>
     */
    #[SerializedName('cacheable_response_headers')]
    public array $cacheableResponseHeaders = [];

    /**
     * @var array<string>
     */
    #[SerializedName('broadcast_headers')]
    public array $broadcastHeaders = ['Content-Type', 'ETag', 'Last-Modified'];

    /**
     * @var array<Url>
     */
    #[SerializedName('preload_urls')]
    public array $urls = [];
}
