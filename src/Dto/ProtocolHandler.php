<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ProtocolHandler
{
    public string $protocol;

    /**
     * @var array<string, mixed>
     */
    #[SerializedName('url_params')]
    public array $urlParameters = [];

    public string $url;
}
