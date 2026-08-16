<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Dto\WorkboxConfig;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxImport;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class WorkboxImportTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyStringWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;
        $workbox->config = new WorkboxConfig();

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itGeneratesCdnImportsWhenUseCdnIsTrue(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;
        $workbox->config->version = '7.4.1';

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString(
            "importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.4.1/workbox-sw.js');",
            $result
        );
        static::assertStringContainsString(
            "importScripts('https://cdn.jsdelivr.net/npm/idb@8/build/umd.js');",
            $result
        );
        static::assertStringNotContainsString('modulePathPrefix', $result);
    }

    #[Test]
    public function itGeneratesLocalImportsWhenUseCdnIsFalse(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = false;
        $workbox->config->workboxPublicUrl = '/workbox';
        $workbox->indexDBPublicUrl = '/idb';

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString("importScripts('/workbox/workbox-sw.js');", $result);
        static::assertStringContainsString("importScripts('/idb/umd.js');", $result);
        static::assertStringContainsString("workbox.setConfig({modulePathPrefix: '/workbox'});", $result);
    }

    #[Test]
    public function itAddsDebugConfigurationWhenDebugIsFalse(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;
        $workbox->config->version = '7.4.1';
        $workbox->config->debug = false;

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString('workbox.setConfig({"debug":false});', $result);
    }

    #[Test]
    public function itDoesNotAddDebugConfigurationWhenDebugIsTrue(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;
        $workbox->config->version = '7.4.1';

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringNotContainsString('workbox.setConfig({"debug":false});', $result);
        // Should not contain any setConfig call for debug since it's true (default)
        static::assertStringNotContainsString('setConfig({"debug', $result);
    }

    #[Test]
    public function itDoesNotAddConfigurationWhenConfigIsNotSet(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;
        $workbox->config->version = '7.4.1';
        // Don't set config to simulate old behavior

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringNotContainsString('workbox.setConfig({"debug', $result);
        static::assertStringContainsString(
            "importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.4.1/workbox-sw.js');",
            $result
        );
    }

    #[Test]
    public function itIncludesDebugCommentsWhenDebugModeIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;
        $workbox->config->version = '7.4.1';

        $rule = $this->createWorkboxImportRule($workbox);

        // When
        $result = $rule->process(debug: true);

        // Then
        static::assertStringContainsString('WORKBOX IMPORT', $result);
        static::assertStringContainsString('END WORKBOX IMPORT', $result);
    }

    #[Test]
    public function itHasCorrectPriority(): void
    {
        // WorkboxImport should have highest priority
        $attributes = (new ReflectionClass(WorkboxImport::class))->getAttributes(AsTaggedItem::class);

        static::assertCount(1, $attributes);
        static::assertSame(1024, $attributes[0]->newInstance()->priority);
    }

    private function createWorkboxImportRule(Workbox $workbox): WorkboxImport
    {
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $serviceWorkerBuilder = new ServiceWorkerBuilder($denormalizer, []);

        return new WorkboxImport($serviceWorkerBuilder, new BasePathResolver());
    }
}
