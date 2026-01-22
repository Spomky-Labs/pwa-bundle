<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ResourceHintsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pwa_resource_hints', [ResourceHintsRuntime::class, 'render'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pwa_resource_hints_header', [ResourceHintsRuntime::class, 'addToRequest']),
        ];
    }
}
