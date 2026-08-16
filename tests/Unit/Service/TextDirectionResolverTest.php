<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Service\TextDirectionResolver;

/**
 * @internal
 */
final class TextDirectionResolverTest extends TestCase
{
    #[Test]
    #[DataProvider('provideLocales')]
    public function theDirectionOfTheLocaleIsDetected(string $locale, string $expected): void
    {
        // Given
        $resolver = new TextDirectionResolver();

        // When
        $direction = $resolver->resolve($locale);

        // Then
        static::assertSame($expected, $direction);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'English' => ['en', 'ltr'];
        yield 'French with a region' => ['fr_CA', 'ltr'];
        yield 'Arabic' => ['ar', 'rtl'];
        yield 'Arabic with a region' => ['ar-EG', 'rtl'];
        yield 'Hebrew' => ['he', 'rtl'];
        yield 'Persian' => ['fa', 'rtl'];
        yield 'Uzbek in the Latin script' => ['uz-Latn-UZ', 'ltr'];
        yield 'Uzbek in the Arabic script' => ['uz-Arab-AF', 'rtl'];
        yield 'Serbian in the Cyrillic script' => ['sr-Cyrl', 'ltr'];
    }

    #[Test]
    public function theConfiguredDirectionsTakePrecedenceOverTheDetection(): void
    {
        // Given
        $resolver = new TextDirectionResolver([
            'ar' => 'ltr',
            'ku' => 'rtl',
        ]);

        // When / Then
        static::assertSame('ltr', $resolver->resolve('ar'));
        static::assertSame('ltr', $resolver->resolve('ar_EG'));
        static::assertSame('rtl', $resolver->resolve('ku'));
    }

    #[Test]
    public function theMostGranularConfiguredLocaleWins(): void
    {
        // Given
        $resolver = new TextDirectionResolver([
            'ar' => 'rtl',
            'ar-DZ' => 'ltr',
        ]);

        // When / Then
        static::assertSame('ltr', $resolver->resolve('ar-DZ'));
        static::assertSame('rtl', $resolver->resolve('ar-EG'));
    }
}
