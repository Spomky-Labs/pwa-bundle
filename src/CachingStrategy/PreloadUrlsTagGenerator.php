<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\Url;

final readonly class PreloadUrlsTagGenerator implements PreloadUrlsGeneratorInterface
{
    /**
     * @var array<Url>
     */
    private array $urls;

    /**
     * @param array{route: string, params: array<string, mixed>, pathTypeReference: int}[] $urls
     */
    public function __construct(
        private string $alias,
        array $urls
    ) {
        $this->urls = array_map(
            static fn (array $url): Url => Url::create($url['route'], $url['params'], $url['pathTypeReference']),
            $urls
        );
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return iterable<Url>
     */
    public function generateUrls(): iterable
    {
        return $this->urls;
    }
}
