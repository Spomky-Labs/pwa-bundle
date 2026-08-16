<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SpomkyLabs\PwaBundle\Service\ApplicationIconCompiler;
use SpomkyLabs\PwaBundle\Service\ManifestCompiler;
use SpomkyLabs\PwaBundle\Tests\LocalizedManifestKernel;

/**
 * @internal
 */
final class LocalizedManifestTest extends TestCase
{
    private const SHORTCUTS = [
        [
            'name' => 'pwa.shortcuts.agenda',
            'url' => '/agenda',
        ],
    ];

    private null|LocalizedManifestKernel $kernel = null;

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->kernel = null;
        parent::tearDown();
    }

    #[Test]
    public function theInlineStrategyCompilesASingleManifestCarryingEveryTranslation(): void
    {
        // Given
        $manifests = $this->compile('inline', [
            'localization_strategy' => 'inline',
            'name' => 'pwa.name',
            'short_name' => 'pwa.short_name',
            'description' => 'pwa.description',
        ]);

        // Then
        static::assertSame(['/site.webmanifest'], array_keys($manifests));
        $manifest = $manifests['/site.webmanifest'];
        static::assertSame('pwa.name', $manifest['name']);
        static::assertSame('en', $manifest['lang']);
        static::assertSame([
            'fr' => "L'application SuperSausage",
            'de' => 'Die SuperWurst-App',
            'ar' => [
                'value' => 'تطبيق سوبر سوسيج',
                'dir' => 'rtl',
            ],
        ], $manifest['name_localized']);
    }

    #[Test]
    public function theLocalesWithoutTranslationAreLeftOut(): void
    {
        // Given
        $manifests = $this->compile('untranslated', [
            'localization_strategy' => 'inline',
            'description' => 'pwa.description',
        ]);

        // Then
        // Only French translates the description, and English resolves to the key itself.
        static::assertSame([
            'fr' => 'Une application de saucisse',
        ], $manifests['/site.webmanifest']['description_localized']);
    }

    #[Test]
    public function anUntranslatedMemberGetsNoLocalizedCounterpart(): void
    {
        // Given
        $manifests = $this->compile('plain', [
            'localization_strategy' => 'inline',
            'name' => 'A plain name, not a translation key',
        ]);

        // Then
        static::assertArrayNotHasKey('name_localized', $manifests['/site.webmanifest']);
    }

    #[Test]
    public function theShortcutsCarryTheirOwnLocalizedMembers(): void
    {
        // Given
        $manifests = $this->compile('shortcuts', [
            'localization_strategy' => 'inline',
            'shortcuts' => self::SHORTCUTS,
        ]);

        // Then
        $shortcut = $manifests['/site.webmanifest']['shortcuts'][0];
        static::assertSame([
            'fr' => "L'agenda du jour",
            'de' => 'Tagesordnung',
        ], $shortcut['name_localized']);
    }

    #[Test]
    public function theExplicitDirectionOfTheManifestIsTheBaseline(): void
    {
        // Given
        $manifests = $this->compile('rtl', [
            'localization_strategy' => 'inline',
            'dir' => 'rtl',
            'name' => 'pwa.name',
        ]);

        // Then
        // The manifest is right to left, so the RTL locale is a plain string and the LTR ones are declined.
        $localized = $manifests['/site.webmanifest']['name_localized'];
        static::assertSame('تطبيق سوبر سوسيج', $localized['ar']);
        static::assertSame([
            'value' => "L'application SuperSausage",
            'dir' => 'ltr',
        ], $localized['fr']);
    }

    #[Test]
    public function theDetectedDirectionCanBeOverridden(): void
    {
        // Given
        $manifests = $this->compile('directions', [
            'localization_strategy' => 'inline',
            'name' => 'pwa.name',
            'locale_directions' => [
                'ar' => 'ltr',
            ],
        ]);

        // Then
        static::assertSame('تطبيق سوبر سوسيج', $manifests['/site.webmanifest']['name_localized']['ar']);
    }

    #[Test]
    public function theLocalizedIconsAreDeclaredAndCompiled(): void
    {
        // Given
        $manifestConfig = [
            'localization_strategy' => 'inline',
            'icons' => [
                [
                    'src' => 'pwa/1920x1920.svg',
                    'sizes' => [48, 96],
                ],
            ],
            'icons_localized' => [
                'de' => [
                    [
                        'src' => 'pwa/screenshots/600x400.svg',
                        'sizes' => [48, 96],
                    ],
                ],
            ],
        ];

        // When
        $manifests = $this->compile('icons', $manifestConfig);
        $iconCompiler = $this->container()
            ->get(ApplicationIconCompiler::class);
        static::assertInstanceOf(ApplicationIconCompiler::class, $iconCompiler);
        $iconUrls = array_keys(iterator_to_array($iconCompiler->getFiles()));

        // Then
        $localizedIcons = $manifests['/site.webmanifest']['icons_localized']['de'];
        static::assertCount(2, $localizedIcons);
        static::assertSame('48x48', $localizedIcons[0]['sizes']);
        foreach ($localizedIcons as $icon) {
            static::assertContains($icon['src'], $iconUrls, 'the localized icon was not compiled');
        }
    }

    #[Test]
    public function theFilesStrategyDropsTheLocalizedMembers(): void
    {
        // Given
        $manifests = $this->compile('files', [
            'public_url' => '/site.{locale}.webmanifest',
            'name' => 'pwa.name',
            'icons_localized' => [
                'de' => [
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [48],
                    ],
                ],
            ],
        ]);

        // Then
        static::assertSame([
            '/site.en.webmanifest',
            '/site.fr.webmanifest',
            '/site.de.webmanifest',
            '/site.ar.webmanifest',
        ], array_keys($manifests));
        foreach ($manifests as $manifest) {
            static::assertArrayNotHasKey('name_localized', $manifest);
            static::assertArrayNotHasKey('icons_localized', $manifest);
        }
    }

    #[Test]
    public function theBothStrategyCombinesTheFilesAndTheLocalizedMembers(): void
    {
        // Given
        $manifests = $this->compile('both', [
            'localization_strategy' => 'both',
            'public_url' => '/site.{locale}.webmanifest',
            'name' => 'pwa.name',
        ]);

        // Then
        static::assertCount(4, $manifests);
        $french = $manifests['/site.fr.webmanifest'];
        static::assertSame("L'application SuperSausage", $french['name']);
        static::assertSame('fr', $french['lang']);
        // The locale of the file itself is already carried by the plain member.
        static::assertArrayNotHasKey('fr', $french['name_localized']);
        static::assertSame('Die SuperWurst-App', $french['name_localized']['de']);
    }

    /**
     * @param array<string, mixed> $manifestConfig
     *
     * @return array<string, array<string, mixed>> Public URL to decoded manifest
     */
    private function compile(string $name, array $manifestConfig): array
    {
        $this->kernel = new LocalizedManifestKernel($name, $manifestConfig);
        $this->kernel->boot();

        $compiler = $this->container()
            ->get(ManifestCompiler::class);
        static::assertInstanceOf(ManifestCompiler::class, $compiler);

        $manifests = [];
        foreach ($compiler->getFiles() as $url => $data) {
            $manifests[$url] = json_decode($data->getData(), true, 512, JSON_THROW_ON_ERROR);
        }

        return $manifests;
    }

    private function container(): ContainerInterface
    {
        static::assertInstanceOf(LocalizedManifestKernel::class, $this->kernel);
        $container = $this->kernel->getContainer()
            ->get('test.service_container');
        static::assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
