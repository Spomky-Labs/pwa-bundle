<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function array_key_exists;
use function sprintf;

final class PreloadUrlsGeneratorManager implements CanLogInterface
{
    /**
     * @var array<string, PreloadUrlsGeneratorInterface>
     */
    private array $generators = [];

    private LoggerInterface $logger;

    /**
     * @param PreloadUrlsGeneratorInterface[] $generators
     */
    public function __construct(
        #[AutowireIterator('spomky_labs_pwa.preload_urls_generator')]
        iterable $generators
    ) {
        $this->logger = new NullLogger();
        foreach ($generators as $generator) {
            $this->add($generator);
        }
    }

    public function add(PreloadUrlsGeneratorInterface $generator, PreloadUrlsGeneratorInterface ...$generators): void
    {
        $this->generators[$generator->getAlias()] = $generator;
        foreach ($generators as $value) {
            $this->logger->debug('Adding preload URL generator', [
                'alias' => $value->getAlias(),
            ]);
            $this->generators[$generator->getAlias()] = $value;
        }
    }

    public function get(string $alias): PreloadUrlsGeneratorInterface
    {
        if (! array_key_exists($alias, $this->generators)) {
            $this->logger->error('The generator with alias does not exist', [
                'alias' => $alias,
            ]);

            throw new InvalidArgumentException(sprintf('The generator with alias "%s" does not exist.', $alias));
        }
        return $this->generators[$alias];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
