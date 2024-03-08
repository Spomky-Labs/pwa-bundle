<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class OfflineFallback implements ServiceWorkerRule
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        private SerializerInterface $serializer,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $options = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $options[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }
        if ($this->workbox->offlineFallback->enabled === false) {
            return $body;
        }
        $options = [
            'pageFallback' => $this->workbox->offlineFallback->pageFallback,
            'imageFallback' => $this->workbox->offlineFallback->imageFallback,
            'fontFallback' => $this->workbox->offlineFallback->fontFallback,
        ];
        $options = array_filter($options, static fn (mixed $v): bool => $v !== null);
        $options = count($options) === 0 ? '' : $this->serializer->serialize($options, 'json', $this->jsonOptions);

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
workbox.routing.setDefaultHandler(new workbox.strategies.NetworkOnly());
workbox.recipes.offlineFallback({$options});
OFFLINE_FALLBACK_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
