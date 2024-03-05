<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class Url
{
    public string $path;

    #[SerializedName('path_type_reference')]
    public int $pathTypeReference = UrlGeneratorInterface::ABSOLUTE_PATH;

    /**
     * @var array<string, mixed>
     */
    public array $params = [];
}
