<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Attribute;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsTagGenerator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use function array_key_exists;
use function is_string;

final class PreloadUrlCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($this->findAllTaggedRoutes($container) as $alias => $urls) {
            $definitionId = sprintf('spomky_labs_pwa.preload_urls_tag_generator.%s', $alias);
            $definition = new ChildDefinition(PreloadUrlsTagGenerator::class);
            $definition
                ->setArguments([
                    '$alias' => $alias,
                    '$urls' => $urls,
                ])
                ->addTag('spomky_labs_pwa.preload_urls_generator')
            ;
            $container->setDefinition($definitionId, $definition);
        }
    }

    /**
     * @return array<string, array{route: string, alias: string, params?: array<string, mixed>, pathTypeReference: int}[]>
     */
    private function findAllTaggedRoutes(ContainerBuilder $container): array
    {
        $routes = [];
        $controllers = $container->findTaggedServiceIds('controller.service_arguments');
        foreach (array_keys($controllers) as $controller) {
            if (! is_string($controller) || ! class_exists($controller)) {
                continue;
            }
            $reflectionClass = new ReflectionClass($controller);
            $result = $this->findAllPreloadAttributesForClass($reflectionClass);
            foreach ($result as $route) {
                if (! array_key_exists($route['alias'], $routes)) {
                    $routes[$route['alias']] = [];
                }
                $routes[$route['alias']][] = $route;
            }
        }

        return $routes;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @return iterable<array{alias: string, route: string, params: array<string, mixed>, pathTypeReference: int}>
     */
    private function findAllPreloadAttributesForClass(ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getAttributes(PreloadUrl::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            try {
                /** @var PreloadUrl $preloadAttribute */
                $preloadAttribute = $attribute->newInstance();
                yield from $this->findAllRoutesToPreload(
                    $preloadAttribute,
                    $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC)
                );
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Unable to create attribute instance: %s', $e->getMessage()), 0, $e);
            }
        }
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(PreloadUrl::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                try {
                    /** @var PreloadUrl $preloadAttribute */
                    $preloadAttribute = $attribute->newInstance();
                    yield from $this->findAllRoutesForMethod($preloadAttribute, $method);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf(
                        'Unable to create attribute instance: %s',
                        $e->getMessage()
                    ), 0, $e);
                }
            }
        }
    }

    /**
     * @param array<ReflectionMethod> $methods
     * @return iterable<array{alias: string, route: string, params: array<string, mixed>, pathTypeReference: int}>
     */
    private function findAllRoutesToPreload(PreloadUrl $preloadAttribute, array $methods): iterable
    {
        foreach ($methods as $method) {
            yield from $this->findAllRoutesForMethod($preloadAttribute, $method);
        }
    }

    /**
     * @return iterable<array{alias: string, route: string, params: array<string, mixed>, pathTypeReference: int}>
     */
    private function findAllRoutesForMethod(PreloadUrl $preloadAttribute, ReflectionMethod $method): iterable
    {
        foreach ($method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            try {
                /** @var Route $routeAttribute */
                $routeAttribute = $attribute->newInstance();
                $routeName = $routeAttribute->getName();
                if ($routeName === null) {
                    continue;
                }
                yield [
                    'alias' => $preloadAttribute->alias,
                    'route' => $routeName,
                    'params' => $preloadAttribute->params,
                    'pathTypeReference' => $preloadAttribute->pathTypeReference,
                ];
            } catch (Throwable) {
                continue;
            }
        }
    }
}
