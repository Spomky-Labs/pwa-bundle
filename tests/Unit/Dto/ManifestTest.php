<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Dto;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\Dto\ShareTarget;
use SpomkyLabs\PwaBundle\Dto\ShareTargetParameters;
use SpomkyLabs\PwaBundle\Dto\Shortcut;
use SpomkyLabs\PwaBundle\Dto\Widget;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @internal
 */
final class ManifestTest extends TestCase
{
    #[Test]
    public function theTranslationDomainReachesEveryTranslatableMember(): void
    {
        // Given
        $shortcut = new Shortcut();
        $shortcut->name = 'app.shortcut';
        $widget = new Widget();
        $widget->name = 'app.widget';
        $widgetScreenshot = new Screenshot();
        $widgetScreenshot->label = 'app.widget_screenshot';
        $widget->screenshots = [$widgetScreenshot];
        $screenshot = new Screenshot();
        $screenshot->label = 'app.screenshot';
        $parameters = new ShareTargetParameters();
        $parameters->title = 'app.share_target';
        $shareTarget = new ShareTarget();
        $shareTarget->params = $parameters;
        $manifest = new Manifest();
        $manifest->name = 'app.name';
        $manifest->shortcuts = [$shortcut];
        $manifest->widgets = [$widget];
        $manifest->screenshots = [$screenshot];
        $manifest->shareTarget = $shareTarget;

        // When
        $manifest->setTranslationDomain('app');

        // Then
        static::assertEquals(new TranslatableMessage('app.name', [], 'app'), $manifest->getName());
        static::assertEquals(new TranslatableMessage('app.shortcut', [], 'app'), $shortcut->getName());
        static::assertEquals(new TranslatableMessage('app.widget', [], 'app'), $widget->getName());
        static::assertEquals(new TranslatableMessage('app.screenshot', [], 'app'), $screenshot->getLabel());
        static::assertEquals(
            new TranslatableMessage('app.widget_screenshot', [], 'app'),
            $widgetScreenshot->getLabel()
        );
        static::assertEquals(new TranslatableMessage('app.share_target', [], 'app'), $parameters->getTitle());
    }
}
