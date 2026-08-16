<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Dto;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\ScreenshotSize;
use function sprintf;

/**
 * @internal
 */
final class ScreenshotSizeTest extends TestCase
{
    #[Test]
    public function everyProfileLabelMatchesItsDimensions(): void
    {
        foreach (ScreenshotSize::cases() as $size) {
            ['width' => $width, 'height' => $height] = $size->getDimensions();
            static::assertStringContainsString(
                sprintf('%d×%d', $width, $height),
                $size->getLabel(),
                sprintf('the label of "%s" does not match the dimensions it returns', $size->value)
            );
        }
    }

    #[Test]
    public function everyProfileAnnouncingAnOrientationMatchesItsFormFactor(): void
    {
        foreach (ScreenshotSize::cases() as $size) {
            $label = $size->getLabel();
            if (str_contains($label, 'Portrait')) {
                static::assertSame(
                    'narrow',
                    $size->getFormFactor(),
                    sprintf('"%s" announces itself as portrait but is wider than it is tall', $size->value)
                );
            }
        }
    }
}
