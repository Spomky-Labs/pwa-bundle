<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\DependencyInjection;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function in_array;

final class SpomkyLabsPwaExtension extends Extension
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
        $container->setParameter('spomky_labs_pwa.routes.reference_type', $config['path_type_reference']);
        $container->setParameter('spomky_labs_pwa.manifest_public_url', $config['manifest_public_url']);

        unset(
            $config['image_processor'],
            $config['web_client'],
            $config['path_type_reference'],
            $config['manifest_public_url'],
        );
        $container->setParameter('spomky_labs_pwa.config', $config);
        if (! in_array($container->getParameter('kernel.environment'), ['dev', 'test'], true)) {
            $container->removeDefinition(PwaDevServerSubscriber::class);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration(self::ALIAS);
    }
}
