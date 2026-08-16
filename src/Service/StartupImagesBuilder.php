<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function assert;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\StartupImages;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class StartupImagesBuilder implements CanLogInterface
{
    private null|StartupImages $startupImages = null;

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

    public function create(): StartupImages
    {
        if ($this->startupImages === null) {
            $this->logger->debug('Creating startup images.', [
                'config' => $this->config,
            ]);
            $result = $this->denormalizer->denormalize($this->config, StartupImages::class);
            assert($result instanceof StartupImages);
            $this->startupImages = $result;
            $this->logger->debug('Startup images created.', [
                'startupImages' => $this->startupImages,
            ]);
        }

        return $this->startupImages;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
