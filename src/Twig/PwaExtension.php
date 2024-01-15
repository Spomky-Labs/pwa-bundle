<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PwaExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pwa', [PwaRuntime::class, 'load'], [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
