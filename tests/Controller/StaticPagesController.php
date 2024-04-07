<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

use SpomkyLabs\PwaBundle\Attribute\PreloadUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[PreloadUrl(alias: 'static-pages')]
final class StaticPagesController extends AbstractController
{
    #[Route('/privacy-policy', name: 'privacy_policy')]
    public function privacyPolicy(string $param1): void
    {
    }

    #[Route('/terms-of-service', name: 'terms_of_service')]
    public function tos(string $param1): void
    {
    }
}
