<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class Url
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $path,
        #[SerializedName('path_type_reference')]
        public int $pathTypeReference = UrlGeneratorInterface::ABSOLUTE_PATH,
        public array $params = [],
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function create(
        string $path,
        array $params = [],
        int $pathTypeReference = UrlGeneratorInterface::ABSOLUTE_PATH
    ): self {
        return new self($path, $pathTypeReference, $params);
    }
}
