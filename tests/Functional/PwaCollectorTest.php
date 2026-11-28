<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\DataCollector\PwaCollector;

/**
 * @internal
 */
final class PwaCollectorTest extends AbstractPwaTestCase
{
    #[Test]
    public static function theManifestOutputCarriesNoConfigurationFlag(): void
    {
        // Given
        $collector = self::getContainer()->get(PwaCollector::class);
        static::assertInstanceOf(PwaCollector::class, $collector);

        // When
        $collector->lateCollect();
        $manifest = json_decode($collector->getData()['manifest']['outputs']['*'], true, 512, JSON_THROW_ON_ERROR);

        // Then
        static::assertIsArray($manifest);
        static::assertArrayNotHasKey('enabled', $manifest);
        static::assertArrayNotHasKey('use_credentials', $manifest);
    }
}
