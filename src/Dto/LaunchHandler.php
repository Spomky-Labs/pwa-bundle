<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class LaunchHandler
{
    /**
     * @var array<string>
     */
    #[SerializedName('client_mode')]
    public array $clientMode = [];
}
