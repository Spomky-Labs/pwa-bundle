<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @param array<string> $examples
 */
function getUrlNode(string $name, string $info, null|array $examples = null): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder($name);
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->info($info)
        ->beforeNormalization()
        ->ifString()
            ->then(static fn (string $v): array => [
                'path' => $v,
            ])
        ->end()
        ->children()
            ->scalarNode('path')
                ->isRequired()
                ->info('The URL or route name.')
                ->example($examples ?? ['https://example.com', 'app_action_route', '/do/action'])
            ->end()
            ->integerNode('path_type_reference')
                ->defaultValue(UrlGeneratorInterface::ABSOLUTE_PATH)
                ->info(
                    'The path type reference to generate paths/URLs. See https://symfony.com/doc/current/routing.html#generating-urls-in-controllers for more information.'
                )
                ->example([
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    UrlGeneratorInterface::NETWORK_PATH,
                    UrlGeneratorInterface::RELATIVE_PATH,
                ])
                ->validate()
                ->ifNotInArray([
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    UrlGeneratorInterface::NETWORK_PATH,
                    UrlGeneratorInterface::RELATIVE_PATH,
                ])
                    ->thenInvalid('Invalid path type reference "%s".')
                ->end()
            ->end()
            ->arrayNode('params')
                ->treatFalseLike([])
                ->treatTrueLike([])
                ->treatNullLike([])
                ->prototype('variable')->end()
                ->info('The parameters of the action. Only used if the action is a route to a controller.')
            ->end()
        ->end()
    ->end();

    return $node;
}
