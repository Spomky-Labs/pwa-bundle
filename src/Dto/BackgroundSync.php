<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BackgroundSync extends Cache
{
    #[SerializedName('queue_name')]
    public string $queueName = '';

    #[SerializedName('match_callback')]
    public string $matchCallback = '';

    #[SerializedName('error_on_4xx')]
    public bool $errorOn4xx = false;

    #[SerializedName('error_on_5xx')]
    public bool $errorOn5xx = true;

    #[SerializedName('expect_redirect')]
    public bool $expectRedirect = false;

    /**
     * @var array<int>
     */
    #[SerializedName('expected_status_codes')]
    public array $expectedStatusCodes = [];

    public string $method = 'POST';

    #[SerializedName('max_retention_time')]
    public int $maxRetentionTime = 1440;

    #[SerializedName('force_sync_fallback')]
    public bool $forceSyncFallback = false;

    #[SerializedName('broadcast_channel')]
    public null|string $broadcastChannel = null;
}
