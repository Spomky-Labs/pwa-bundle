<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

interface HtmlRendererInterface
{
    /**
     * Paints an HTML document and returns the PNG bytes of a viewport exactly $width x $height device
     * pixels wide. iOS discards a startup image whose dimensions do not match the screen to the pixel, so
     * an implementation that cannot honour the requested size shall throw rather than return a fitting-ish
     * image.
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    public function capture(string $html, int $width, int $height): string;
}
