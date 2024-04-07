<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Attribute;

use Attribute;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class PreloadUrl
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $alias,
        public array $params = [],
        public int $pathTypeReference = UrlGeneratorInterface::ABSOLUTE_PATH,
    ) {
    }
}
