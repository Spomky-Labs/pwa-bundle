<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function array_key_exists;

final class PreloadUrlsGeneratorManager
{
    /**
     * @var array<string, PreloadUrlsGeneratorInterface>
     */
    private array $generators = [];

    /**
     * @param PreloadUrlsGeneratorInterface[] $generators
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.preload_urls_generator')]
        iterable $generators
    ) {
        foreach ($generators as $generator) {
            $this->add($generator);
        }
    }

    public function add(PreloadUrlsGeneratorInterface $generator, PreloadUrlsGeneratorInterface ...$generators): void
    {
        $this->generators[$generator->getAlias()] = $generator;
        foreach ($generators as $value) {
            $this->generators[$generator->getAlias()] = $value;
        }
    }

    public function get(string $alias): PreloadUrlsGeneratorInterface
    {
        if (! array_key_exists($alias, $this->generators)) {
            throw new InvalidArgumentException(sprintf('The generator with alias "%s" does not exist.', $alias));
        }
        return $this->generators[$alias];
    }
}
