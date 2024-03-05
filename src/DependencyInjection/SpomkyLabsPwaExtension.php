<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\DependencyInjection;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function in_array;

final class SpomkyLabsPwaExtension extends Extension implements PrependExtensionInterface
{
    private const ALIAS = 'pwa';

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        if ($config['image_processor'] !== null) {
            $container->setAlias(ImageProcessor::class, $config['image_processor']);
        }
        if ($config['web_client'] !== null) {
            $container->setAlias('pwa.web_client', $config['web_client']);
        }
        $container->setParameter(
            'spomky_labs_pwa.asset_public_prefix',
            '/' . trim((string) $config['asset_public_prefix'], '/')
        );
        $container->setParameter('spomky_labs_pwa.routes.reference_type', $config['path_type_reference']);
        $serviceWorkerConfig = $config['serviceworker'];
        $manifestConfig = $config['manifest'];
        if ($serviceWorkerConfig['enabled'] === true && $manifestConfig['enabled'] === true) {
            $manifestConfig['serviceworker'] = $serviceWorkerConfig;
        }

        /*** Manifest ***/
        $container->setParameter('spomky_labs_pwa.manifest.enabled', $config['manifest']['enabled']);
        $container->setParameter('spomky_labs_pwa.manifest.public_url', $config['manifest']['public_url'] ?? null);
        $container->setParameter('spomky_labs_pwa.manifest.config', $manifestConfig);

        /*** Service Worker ***/
        $container->setParameter('spomky_labs_pwa.sw.enabled', $config['serviceworker']['enabled']);
        $container->setParameter('spomky_labs_pwa.sw.public_url', $config['serviceworker']['dest'] ?? null);
        $container->setParameter('spomky_labs_pwa.sw.config', $serviceWorkerConfig);

        if (! in_array($container->getParameter('kernel.environment'), ['dev', 'test'], true)) {
            $container->removeDefinition(PwaDevServerSubscriber::class);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration(self::ALIAS);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['FrameworkBundle'])) {
            foreach ($container->getExtensions() as $name => $extension) {
                if ($name !== 'framework') {
                    continue;
                }
                $config = $container->getExtensionConfig($name);
                foreach ($config as $c) {
                    if (! isset($c['asset_mapper']['public_prefix'])) {
                        continue;
                    }
                    $container->prependExtensionConfig('pwa', [
                        'asset_public_prefix' => $c['asset_mapper']['public_prefix'],
                    ]);
                }
            }
        }
    }
}
