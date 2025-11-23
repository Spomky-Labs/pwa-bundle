<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use ReflectionClass;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\TwigTest;

class InstanceOfExtension
{
    public function getTests(): array
    {
        return [new TwigTest('instanceof', $this->isInstanceOf(...))];
    }

    /**
     * @param class-string $instance
     */
    #[AsTwigFilter('instanceof')]
    #[AsTwigFunction('instanceof')]
    public function isInstanceOf(object $var, string $instance): bool
    {
        return (new ReflectionClass($instance))->isInstance($var);
    }
}
