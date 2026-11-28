<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use function iterator_to_array;
use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SpomkyLabs\PwaBundle\DataCollector\PwaCollector;
use SpomkyLabs\PwaBundle\Service\ManifestCompiler;
use SpomkyLabs\PwaBundle\Tests\ManifestKernel;
use stdClass;

/**
 * The manifest is serialized in two places: the compiled file, also served by the dev server, and the preview of the
 * profiler panel. Both must give the same JSON.
 *
 * @internal
 */
final class ManifestSerializationTest extends TestCase
{
    private null|ManifestKernel $kernel = null;

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->kernel = null;
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideOutputs(): iterable
    {
        yield 'compiled file' => ['compiled'];
        yield 'profiler preview' => ['profiler'];
    }

    #[Test]
    #[DataProvider('provideOutputs')]
    public function theEdgeSidePanelOptInIsAnEmptyObject(string $output): void
    {
        // Given
        $manifestConfig = [
            'name' => 'My app',
            'edge_side_panel' => null,
        ];

        // When
        $manifest = $this->serialize($output, 'edge_side_panel_opt_in', $manifestConfig);

        // Then
        static::assertObjectHasProperty('edge_side_panel', $manifest);
        static::assertEquals(new stdClass(), $manifest->edge_side_panel);
    }

    #[Test]
    #[DataProvider('provideOutputs')]
    public function anEmptyCollectionIsLeftOut(string $output): void
    {
        // Given
        $manifestConfig = [
            'name' => 'My app',
        ];

        // When
        $manifest = $this->serialize($output, 'empty_collection', $manifestConfig);

        // Then
        static::assertObjectNotHasProperty('shortcuts', $manifest);
    }

    #[Test]
    #[DataProvider('provideOutputs')]
    public function noLangIsWrittenWhenNothingProvidesIt(string $output): void
    {
        // Given
        $manifestConfig = [
            'name' => 'My app',
        ];

        // When
        $manifest = $this->serialize($output, 'no_lang', $manifestConfig);

        // Then
        static::assertObjectNotHasProperty('lang', $manifest);
    }

    #[Test]
    #[DataProvider('provideOutputs')]
    public function theConfiguredLangIsWritten(string $output): void
    {
        // Given
        $manifestConfig = [
            'name' => 'My app',
            'lang' => 'ar',
        ];

        // When
        $manifest = $this->serialize($output, 'configured_lang', $manifestConfig);

        // Then
        static::assertObjectHasProperty('lang', $manifest);
        static::assertSame('ar', $manifest->lang);
    }

    /**
     * @param array<string, mixed> $manifestConfig
     */
    private function serialize(string $output, string $name, array $manifestConfig): stdClass
    {
        $this->kernel = new ManifestKernel($name, $manifestConfig);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()
            ->get('test.service_container');
        static::assertInstanceOf(ContainerInterface::class, $container);

        if ($output === 'profiler') {
            $collector = $container->get(PwaCollector::class);
            static::assertInstanceOf(PwaCollector::class, $collector);
            $collector->lateCollect();
            $json = $collector->getData()['manifest']['outputs']['*'];
        } else {
            $compiler = $container->get(ManifestCompiler::class);
            static::assertInstanceOf(ManifestCompiler::class, $compiler);
            $json = iterator_to_array($compiler->getFiles())['/site.webmanifest']->getData();
        }

        static::assertIsString($json);
        $manifest = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        static::assertInstanceOf(stdClass::class, $manifest);

        return $manifest;
    }
}
