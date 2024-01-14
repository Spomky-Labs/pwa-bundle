<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ServiceWorker
{
    public null|string $filepath = null;

    public null|string $src = null;

    public null|string $scope = null;

    #[SerializedName('use_cache')]
    public null|bool $useCache = null;
}
