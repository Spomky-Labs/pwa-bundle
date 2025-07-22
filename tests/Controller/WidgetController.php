<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

use RuntimeException;
use SpomkyLabs\PwaBundle\Attribute\PreloadUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class WidgetController extends AbstractController
{
    #[PreloadUrl(alias: 'widgets')]
    #[Route('/widget/template', name: 'app_widget_template')]
    public function widgetTemplate(): never
    {
        // This method is intentionally left empty.
        throw new RuntimeException('This method should not be called.');
    }

    #[PreloadUrl(alias: 'widgets')]
    #[Route('/widget/data', name: 'app_widget_data')]
    public function widgetData(): never
    {
        // This method is intentionally left empty.
        throw new RuntimeException('This method should not be called.');
    }
}
