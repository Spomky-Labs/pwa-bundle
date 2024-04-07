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

    /**
     * @param array<string, mixed> $params
     */
    public static function create(
        string $path,
        array $params = [],
        int $pathTypeReference = UrlGeneratorInterface::ABSOLUTE_PATH
    ): self {
        $url = new self();
        $url->path = $path;
        $url->pathTypeReference = $pathTypeReference;
        $url->params = $params;

        return $url;
    }
}
