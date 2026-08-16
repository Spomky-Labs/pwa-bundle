<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

enum Orientation: string
{
    case PORTRAIT = 'portrait';

    case LANDSCAPE = 'landscape';
}
