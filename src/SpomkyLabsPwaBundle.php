<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle;

use SpomkyLabs\PwaBundle\DependencyInjection\SpomkyLabsPwaExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SpomkyLabsPwaBundle extends Bundle
{
    public function getContainerExtension(): SpomkyLabsPwaExtension
    {
        return new SpomkyLabsPwaExtension();
    }
}
