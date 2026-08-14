<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use function sprintf;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class CreateConfigCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public function theConfigurationIsCreatedFromTheOptions(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();

        // When
        $commandTester->execute([
            '--path' => $path,
            '--name' => 'HackerNews PWA',
            '--short-name' => 'HackerNews',
            '--description' => 'A HackerNews implementation based on Symfony',
            '--start-url' => '/news',
            '--display' => 'minimal-ui',
            '--theme-color' => '#ff6600',
            '--background-color' => '#ffffff',
            '--icon' => 'pwa/1920x1920.svg',
            '--image-processor' => 'imagick',
            '--no-serviceworker' => true,
        ], [
            'interactive' => false,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists($path);
        $configuration = $this->loadConfiguration($path);
        static::assertSame([
            'image_processor' => 'imagick',
            'manifest' => [
                'enabled' => true,
                'name' => 'HackerNews PWA',
                'short_name' => 'HackerNews',
                'description' => 'A HackerNews implementation based on Symfony',
                'start_url' => '/news',
                'display' => 'minimal-ui',
                'theme_color' => '#ff6600',
                'background_color' => '#ffffff',
                'icons' => [
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [48, 72, 96, 128, 144, 168, 192, 256, 512],
                        'format' => 'png',
                    ],
                ],
            ],
            'favicons' => [
                'enabled' => true,
                'default' => [
                    'src' => 'pwa/1920x1920.svg',
                    'background_color' => '#ffffff',
                ],
            ],
        ], $configuration);
    }

    #[Test]
    public function theGeneratedConfigurationIsValid(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $commandTester->execute([
            '--path' => $path,
            '--name' => 'HackerNews PWA',
            '--icon' => 'pwa/1920x1920.svg',
            '--image-processor' => 'imagick',
            '--no-serviceworker' => true,
        ], [
            'interactive' => false,
        ]);

        // When
        $processedConfiguration = (new Processor())->processConfiguration(
            new Configuration(new SpomkyLabsPwaBundle(), null, 'pwa'),
            [$this->loadConfiguration($path)]
        );

        // Then
        static::assertSame('HackerNews PWA', $processedConfiguration['manifest']['name']);
        static::assertTrue($processedConfiguration['favicons']['enabled']);
        static::assertCount(9, $processedConfiguration['manifest']['icons']);
    }

    #[Test]
    public function theConfigurationIsCreatedInteractively(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $commandTester->setInputs([
            'HackerNews PWA',
            'HackerNews',
            'A HackerNews implementation based on Symfony',
            '/news',
            'minimal-ui',
            '#ff6600',
            '#ffffff',
            '', // No source image: the icons and the favicons are skipped
            'no', // No service worker
        ]);

        // When
        $commandTester->execute([
            '--path' => $path,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertSame([
            'manifest' => [
                'enabled' => true,
                'name' => 'HackerNews PWA',
                'short_name' => 'HackerNews',
                'description' => 'A HackerNews implementation based on Symfony',
                'start_url' => '/news',
                'display' => 'minimal-ui',
                'theme_color' => '#ff6600',
                'background_color' => '#ffffff',
            ],
        ], $this->loadConfiguration($path));
    }

    #[Test]
    public function theProposedValuesAreUsedWhenTheAnswersAreEmpty(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $commandTester->setInputs(['', '', '', '', '', '', '', '', 'no']);

        // When
        $commandTester->execute([
            '--path' => $path,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        $configuration = $this->loadConfiguration($path);
        static::assertSame('/', $configuration['manifest']['start_url']);
        static::assertSame('standalone', $configuration['manifest']['display']);
        static::assertSame('#ffffff', $configuration['manifest']['theme_color']);
        static::assertArrayNotHasKey('description', $configuration['manifest']);
    }

    #[Test]
    public function theServiceWorkerSourceFileIsCreated(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $serviceWorkerPath = sprintf('%s/assets/pwa-test-sw.js', self::$kernel->getProjectDir());
        $filesystem = $this->filesystem();
        $filesystem->remove($serviceWorkerPath);

        try {
            // When
            $commandTester->execute([
                '--path' => $path,
                '--name' => 'HackerNews PWA',
                '--serviceworker' => true,
                '--serviceworker-src' => 'pwa-test-sw.js',
            ], [
                'interactive' => false,
            ]);

            // Then
            $commandTester->assertCommandIsSuccessful();
            static::assertSame([
                'enabled' => true,
                'src' => 'pwa-test-sw.js',
            ], $this->loadConfiguration($path)['serviceworker']);
            static::assertFileExists($serviceWorkerPath);
            static::assertSame('', file_get_contents($serviceWorkerPath));
        } finally {
            $filesystem->remove($serviceWorkerPath);
        }
    }

    #[Test]
    public function nothingIsWrittenOnDryRun(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();

        // When
        $commandTester->execute([
            '--path' => $path,
            '--name' => 'HackerNews PWA',
            '--no-serviceworker' => true,
            '--dry-run' => true,
        ], [
            'interactive' => false,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileDoesNotExist($path);
        $configuration = Yaml::parse($commandTester->getDisplay());
        static::assertSame('HackerNews PWA', $configuration['pwa']['manifest']['name']);
    }

    #[Test]
    public function anExistingFileIsNotOverwrittenWithoutTheForceOption(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $this->filesystem()
            ->dumpFile($path, 'pwa: ~');

        // When
        $commandTester->execute([
            '--path' => $path,
            '--name' => 'HackerNews PWA',
            '--no-serviceworker' => true,
        ], [
            'interactive' => false,
        ]);

        // Then
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
        static::assertStringContainsString('--force', $commandTester->getDisplay());
        static::assertStringEqualsFile($path, 'pwa: ~');
    }

    #[Test]
    public function anExistingFileIsOverwrittenWithTheForceOption(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();
        $this->filesystem()
            ->dumpFile($path, 'pwa: ~');

        // When
        $commandTester->execute([
            '--path' => $path,
            '--name' => 'HackerNews PWA',
            '--no-serviceworker' => true,
            '--force' => true,
        ], [
            'interactive' => false,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertSame('HackerNews PWA', $this->loadConfiguration($path)['manifest']['name']);
    }

    #[Test]
    public function theDisplayModeIsValidated(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();

        // When
        $commandTester->execute([
            '--path' => $path,
            '--display' => 'full-screen',
        ], [
            'interactive' => false,
        ]);

        // Then
        static::assertSame(Command::INVALID, $commandTester->getStatusCode());
        static::assertStringContainsString('is not supported', $commandTester->getDisplay());
        static::assertFileDoesNotExist($path);
    }

    #[Test]
    public function theFaviconsCannotBeEnabledWithoutASourceImage(): void
    {
        // Given
        $commandTester = $this->createCommandTester();
        $path = $this->configurationPath();

        // When
        $commandTester->execute([
            '--path' => $path,
            '--favicons' => true,
        ], [
            'interactive' => false,
        ]);

        // Then
        static::assertSame(Command::INVALID, $commandTester->getStatusCode());
        static::assertStringContainsString('--icon', $commandTester->getDisplay());
        static::assertFileDoesNotExist($path);
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester(self::$application->find('pwa:create:config'));
    }

    private function configurationPath(): string
    {
        return sprintf('%s/samples/pwa.yaml', self::$kernel->getCacheDir());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfiguration(string $path): array
    {
        $configuration = Yaml::parseFile($path);
        static::assertIsArray($configuration);
        static::assertArrayHasKey('pwa', $configuration);
        static::assertIsArray($configuration['pwa']);

        return $configuration['pwa'];
    }

    private function filesystem(): Filesystem
    {
        $filesystem = self::getContainer()->get(Filesystem::class);
        static::assertInstanceOf(Filesystem::class, $filesystem);

        return $filesystem;
    }
}
