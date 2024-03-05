<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function in_array;

final class SpomkyLabsPwaBundle extends AbstractBundle
{
    protected string $extensionAlias = 'pwa';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('Resources/config/definition/*.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.php');

        if ($config['image_processor'] !== null) {
            $builder->setAlias(ImageProcessor::class, $config['image_processor']);
        }
        if ($config['web_client'] !== null) {
            $builder->setAlias('pwa.web_client', $config['web_client']);
        }
        $serviceWorkerConfig = $config['serviceworker'];
        $manifestConfig = $config['manifest'];
        if ($serviceWorkerConfig['enabled'] === true && $manifestConfig['enabled'] === true) {
            $manifestConfig['serviceworker'] = $serviceWorkerConfig;
        }
        $builder->setParameter(
            'spomky_labs_pwa.asset_public_prefix',
            '/' . trim((string) $config['asset_public_prefix'], '/')
        );

        /*** Manifest ***/
        $builder->setParameter('spomky_labs_pwa.manifest.enabled', $config['manifest']['enabled']);
        $builder->setParameter('spomky_labs_pwa.manifest.public_url', $config['manifest']['public_url'] ?? null);
        $builder->setParameter('spomky_labs_pwa.manifest.config', $manifestConfig);

        /*** Service Worker ***/
        $builder->setParameter('spomky_labs_pwa.sw.enabled', $config['serviceworker']['enabled']);
        $builder->setParameter('spomky_labs_pwa.sw.public_url', $config['serviceworker']['dest'] ?? null);
        $builder->setParameter('spomky_labs_pwa.sw.config', $serviceWorkerConfig);

        if (! in_array($builder->getParameter('kernel.environment'), ['dev', 'test'], true)) {
            $builder->removeDefinition(PwaDevServerSubscriber::class);
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $bundles = $builder->getParameter('kernel.bundles');
        if (isset($bundles['FrameworkBundle'])) {
            foreach ($builder->getExtensions() as $name => $extension) {
                if ($name !== 'framework') {
                    continue;
                }
                $config = $builder->getExtensionConfig($name);
                foreach ($config as $c) {
                    if (! isset($c['asset_mapper']['public_prefix'])) {
                        continue;
                    }
                    $builder->prependExtensionConfig('pwa', [
                        'asset_public_prefix' => $c['asset_mapper']['public_prefix'],
                    ]);
                }
            }
        }
    }
}
