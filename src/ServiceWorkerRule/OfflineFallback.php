<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class OfflineFallback implements ServiceWorkerRuleInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorker $serviceWorker,
        private readonly SerializerInterface $serializer,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false || ! isset($this->workbox->offlineFallback)) {
            $this->logger->debug('Workbox is disabled or offline fallback is not set. The rule will not be applied.');
            return '';
        }

        $cacheName = $this->workbox->offlineFallback->cacheName ?? 'offline';
        $options = [
            'pageFallback' => $this->workbox->offlineFallback->pageFallback,
            'imageFallback' => $this->workbox->offlineFallback->imageFallback,
            'fontFallback' => $this->workbox->offlineFallback->fontFallback,
        ];
        $options = array_filter($options, static fn (mixed $v): bool => $v !== null);
        if (count($options) === 0) {
            return '';
        }
        $urls = count($options) === 0 ? '' : $this->serializer->serialize(
            array_values($options),
            'json',
            $this->serializerOptions($debug)
        );
        $fallbacks = count($options) === 0 ? '' : $this->serializer->serialize(
            $options,
            'json',
            $this->serializerOptions($debug)
        );

        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** OFFLINE FALLBACK ****************************************************/
// The configuration is set to provide offline fallbacks

DEBUG_COMMENT;
        }

        $declaration .= <<<OFFLINE_FALLBACK_STRATEGY
workbox.routing.setDefaultHandler(new workbox.strategies.NetworkOnly());
registerInstallTask(() => {
  return openCache(registerCacheName('{$cacheName}')).then(cache =>
    cache.addAll({$urls})
  );
}, 10);
workbox.routing.setCatchHandler(async ({ request }) => {
  const dest = request.destination;
  const cache = await openCache('{$cacheName}');
  const fallbacks = {$fallbacks};

  if (dest === 'document' && fallbacks.pageFallback) {
    return await cache.match(fallbacks.pageFallback) ?? Response.error();
  }
  if (dest === 'image' && fallbacks.imageFallback) {
    return await cache.match(fallbacks.imageFallback) ?? Response.error();
  }
  if (dest === 'font' && fallbacks.fontFallback) {
    return await cache.match(fallbacks.fontFallback) ?? Response.error();
  }

  return Response.error();
});

OFFLINE_FALLBACK_STRATEGY;

        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END OFFLINE FALLBACK ****************************************************/




DEBUG_COMMENT;
        }
        $this->logger->debug('Offline fallback rule added.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializerOptions(bool $debug): array
    {
        $jsonOptions = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $jsonOptions[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }

        return $jsonOptions;
    }
}
