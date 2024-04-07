<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

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
    public function widgetTemplate(): void
    {
    }

    #[PreloadUrl(alias: 'widgets')]
    #[Route('/widget/data', name: 'app_widget_data')]
    public function widgetData(): void
    {
    }
}
