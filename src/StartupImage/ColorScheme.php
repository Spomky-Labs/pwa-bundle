<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use function sprintf;

enum ColorScheme: string
{
    case LIGHT = 'light';

    case DARK = 'dark';

    public function mediaQuery(): string
    {
        return sprintf('(prefers-color-scheme: %s)', $this->value);
    }
}
