<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WorkboxConfig
{
    #[SerializedName('use_cdn')]
    public bool $useCDN = false;

    public string $version = '7.4.1';

    #[SerializedName('workbox_public_url')]
    public string $workboxPublicUrl = '/workbox';

    public bool $debug = true;
}
