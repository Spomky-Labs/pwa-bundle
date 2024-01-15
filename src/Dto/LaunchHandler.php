<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class LaunchHandler
{
    #[SerializedName('client_mode')]
    /**
     * @var string|array<string>
     */
    public array $clientMode = [];
}
