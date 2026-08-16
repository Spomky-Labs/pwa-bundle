<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class StartupImages
{
    public bool $enabled = false;

    /**
     * The Twig template describing the image. When left null, the image is composited by the image
     * processor: the source image, centered over the background color.
     */
    public null|string $template = null;

    public StartupImageTheme $default;

    public null|StartupImageTheme $dark = null;

    public bool $monochrome = false;
}
