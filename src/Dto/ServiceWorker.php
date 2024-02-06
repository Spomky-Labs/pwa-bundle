<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ServiceWorker
{
    public bool $enabled;

    public Asset $src;

    public string $dest;

    public null|string $scope = null;

    #[SerializedName('use_cache')]
    public null|bool $useCache = null;

    public Workbox $workbox;
}
