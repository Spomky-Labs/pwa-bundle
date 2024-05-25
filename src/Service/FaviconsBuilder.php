<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function assert;

final class FaviconsBuilder implements CanLogInterface
{
    private null|Favicons $favicons = null;

    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
    ) {
        $this->logger = new NullLogger();
    }

    public function create(): Favicons
    {
        if ($this->favicons === null) {
            $this->logger->debug('Creating favicons.', [
                'config' => $this->config,
            ]);
            $result = $this->denormalizer->denormalize($this->config, Favicons::class);
            assert($result instanceof Favicons);
            $this->favicons = $result;
            $this->logger->debug('Favicons created.', [
                'favicons' => $this->favicons,
            ]);
        }

        return $this->favicons;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
