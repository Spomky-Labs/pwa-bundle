<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BackgroundSync
{
    #[SerializedName('queue_name')]
    public string $queueName;

    public string $regex;

    public string $method;

    #[SerializedName('max_retention_time')]
    public int $maxRetentionTime;

    #[SerializedName('force_sync_callback')]
    public bool $forceSyncFallback;
}
