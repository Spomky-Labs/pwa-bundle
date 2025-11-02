<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

final readonly class PreloadUrlsTagGeneratorFactory
{
    /**
     * @param array{route: string, params: array<string, mixed>, pathTypeReference: int}[] $urls
     */
    public static function create(string $alias, array $urls): PreloadUrlsTagGenerator
    {
        return new PreloadUrlsTagGenerator($alias, $urls);
    }
}
