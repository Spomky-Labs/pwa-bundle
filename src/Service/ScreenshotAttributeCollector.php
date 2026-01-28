<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function class_exists;
use function is_string;
use ReflectionAttribute;
use ReflectionClass;
use SpomkyLabs\PwaBundle\Attribute\Screenshot as ScreenshotAttribute;
use SpomkyLabs\PwaBundle\Dto\ScreenshotConfiguration;
use function sprintf;
use function str_contains;
use function str_replace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

readonly class ScreenshotAttributeCollector
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
        private RouterInterface $router,
    ) {
    }

    /**
     * Collect all screenshot configurations from #[Screenshot] attributes.
     *
     * @param null|string $filterLocale If set, only return configurations for this locale
     * @return array{configurations: array<ScreenshotConfiguration>}
     */
    public function collect(null|string $filterLocale = null): array
    {
        $configurations = [];
        $defaultOutput = sprintf('%s/assets/screenshots/', $this->projectDir);
        $routeCollection = $this->router->getRouteCollection();

        foreach ($routeCollection as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            if ($controller === null || ! is_string($controller)) {
                continue;
            }

            // Parse controller string (e.g., "App\Controller\HomeController::index" or invokable)
            if (str_contains($controller, '::')) {
                [$className, $methodName] = explode('::', $controller, 2);
            } else {
                $className = $controller;
                $methodName = '__invoke';
            }

            if (! class_exists($className)) {
                continue;
            }

            try {
                $reflectionClass = new ReflectionClass($className);
                if (! $reflectionClass->hasMethod($methodName)) {
                    continue;
                }

                $reflectionMethod = $reflectionClass->getMethod($methodName);
                $attributes = $reflectionMethod->getAttributes(
                    ScreenshotAttribute::class,
                    ReflectionAttribute::IS_INSTANCEOF
                );

                foreach ($attributes as $attribute) {
                    /** @var ScreenshotAttribute $screenshotAttr */
                    $screenshotAttr = $attribute->newInstance();

                    $localesToProcess = $screenshotAttr->locales !== [] ? $screenshotAttr->locales : [null];

                    foreach ($localesToProcess as $locale) {
                        // Filter by locale if requested
                        if ($filterLocale !== null && $locale !== null && $locale !== $filterLocale) {
                            continue;
                        }

                        $config = $this->buildConfiguration($screenshotAttr, $routeName, $locale, $defaultOutput);

                        if ($config !== null) {
                            $configurations[] = $config;
                        }
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return [
            'configurations' => $configurations,
        ];
    }

    /**
     * Build a ScreenshotConfiguration from an attribute.
     */
    public function buildConfiguration(
        ScreenshotAttribute $attr,
        string $routeName,
        null|string $locale,
        null|string $defaultOutput = null
    ): null|ScreenshotConfiguration {
        $defaultOutput ??= sprintf('%s/assets/screenshots/', $this->projectDir);

        $parameters = $attr->parameters;
        if ($locale !== null) {
            $parameters['_locale'] = $locale;
        }

        $baseFilename = $attr->name ?? str_replace('.', '-', $routeName);
        $filename = $locale !== null ? sprintf('%s-%s', $baseFilename, $locale) : $baseFilename;

        $configData = [
            'route' => $routeName,
            'parameters' => $parameters,
            'sizes' => $attr->sizes,
            'filename' => $filename,
            'output' => $attr->output ?? $defaultOutput,
            'locale' => $locale,
            'label' => $attr->label,
            'format' => $attr->format ?? 'png',
        ];

        if ($attr->platform !== null) {
            $configData['platform'] = $attr->platform;
        }

        try {
            return ScreenshotConfiguration::fromArray($configData, $defaultOutput);
        } catch (Throwable) {
            return null;
        }
    }

    public function getDefaultOutput(): string
    {
        return sprintf('%s/assets/screenshots/', $this->projectDir);
    }
}
