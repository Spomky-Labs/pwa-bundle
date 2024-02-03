<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class DummyController extends AbstractController
{
    #[Route('/privacy-policy', name: 'privacy_policy')]
    public function privacyPolicy(string $param1): void
    {
    }

    #[Route('/terms-of-service', name: 'terms_of_service')]
    public function tos(string $param1): void
    {
    }

    #[Route('/audio-file-handler/{param1}', name: 'audio_file_handler')]
    public function dummy1(string $param1): void
    {
    }

    #[Route('/shared-content-receiver/{param1}/{param2}', name: 'shared_content_receiver')]
    public function dummy2(string $param1, string $param2): void
    {
    }

    #[Route('/agenda/{date}', name: 'agenda')]
    public function agenda(string $date): void
    {
    }

    #[Route('/widget/template', name: 'app_widget_template')]
    public function widgetTemplate(): void
    {
    }

    #[Route('/widget/data', name: 'app_widget_data')]
    public function widgetData(): void
    {
    }
}
