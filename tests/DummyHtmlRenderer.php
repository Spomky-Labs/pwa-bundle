<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use SpomkyLabs\PwaBundle\StartupImage\HtmlRendererInterface;
use function sprintf;

/**
 * Records the documents it is handed instead of starting a browser to paint them.
 *
 * @internal
 */
final class DummyHtmlRenderer implements HtmlRendererInterface
{
    /**
     * @var list<array{html: string, width: int, height: int}>
     */
    public array $captures = [];

    public function capture(string $html, int $width, int $height): string
    {
        $this->captures[] = [
            'html' => $html,
            'width' => $width,
            'height' => $height,
        ];

        return sprintf('PNG %dx%d', $width, $height);
    }
}
