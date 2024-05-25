<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CompilerPass;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class LoggerCompilerPass implements CompilerPassInterface
{
    public const TAG = 'spomky_labs_pwa.can_log';

    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('spomky_labs_pwa.logger')) {
            return;
        }

        $logger = $container->findDefinition(LoggerInterface::class);
        $services = $container->findTaggedServiceIds(self::TAG);
        foreach ($services as $id => $tags) {
            $idDefinition = $container->findDefinition($id);
            $idDefinition->addMethodCall('setLogger', [$logger]);
        }
    }
}
