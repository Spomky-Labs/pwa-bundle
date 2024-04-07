<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use ReflectionClass;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction('instanceof', $this->isInstanceOf(...))];
    }

    public function getFilters(): array
    {
        return [new TwigFilter('instanceof', $this->isInstanceOf(...))];
    }

    public function getTests(): array
    {
        return [new TwigTest('instanceof', $this->isInstanceOf(...))];
    }

    /**
     * @param class-string $instance
     */
    public function isInstanceOf(object $var, string $instance): bool
    {
        $reflexionClass = new ReflectionClass($instance);
        return $reflexionClass->isInstance($var);
    }
}
