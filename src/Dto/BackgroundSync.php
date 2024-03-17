<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BackgroundSync extends Cache
{
    #[SerializedName('queue_name')]
    public string $queueName;

    #[SerializedName('match_callback')]
    public string $matchCallback;

    public string $method;

    #[SerializedName('max_retention_time')]
    public int $maxRetentionTime;

    #[SerializedName('force_sync_fallback')]
    public bool $forceSyncFallback;

    #[SerializedName('broadcast_channel')]
    public null|string $broadcastChannel = null;
}
