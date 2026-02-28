<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle;

use function in_array;
use SpomkyLabs\PwaBundle\CompilerPass\LoggerCompilerPass;
use SpomkyLabs\PwaBundle\CompilerPass\PreloadUrlCompilerPass;
use SpomkyLabs\PwaBundle\EventListener\PwaDevServerListener;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SpomkyLabsPwaBundle extends AbstractBundle
{
    protected string $extensionAlias = 'pwa';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('Resources/config/definition/*.php');
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PreloadUrlCompilerPass());
        $container->addCompilerPass(new LoggerCompilerPass());
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.php');

        /** @var string|null $imageProcessor */
        $imageProcessor = $config['image_processor'];
        if ($imageProcessor !== null) {
            $builder->setAlias(ImageProcessorInterface::class, $imageProcessor);
        }

        /** @var string|null $assetCompiler */
        $assetCompiler = $config['asset_compiler'];
        if ($assetCompiler !== null) {
            $builder->setParameter('spomky_labs_pwa.asset_compiler', $assetCompiler);
        }

        /** @var string|null $webClient */
        $webClient = $config['web_client'];
        if ($webClient !== null) {
            $builder->setAlias('pwa.web_client', $webClient);
        }

        /** @var string $userAgent */
        $userAgent = $config['user_agent'];
        $builder->setParameter('spomky_labs_pwa.screenshot_user_agent', $userAgent);

        /** @var array{enabled: bool, dest?: string} $serviceWorkerConfig */
        $serviceWorkerConfig = $config['serviceworker'];
        /** @var array{enabled: bool, public_url?: string} $manifestConfig */
        $manifestConfig = $config['manifest'];
        if ($serviceWorkerConfig['enabled'] === true) {
            $manifestConfig['serviceworker'] = $serviceWorkerConfig;
        }

        /** @var string|null $logger */
        $logger = $config['logger'];
        if ($logger !== null) {
            $builder->setAlias('spomky_labs_pwa.logger', $logger);
        }

        /*** Manifest ***/
        $builder->setParameter('spomky_labs_pwa.manifest.enabled', $manifestConfig['enabled']);
        $builder->setParameter('spomky_labs_pwa.manifest.public_url', $manifestConfig['public_url'] ?? null);
        $builder->setParameter('spomky_labs_pwa.manifest.config', $manifestConfig);

        /*** Favicons ***/
        /** @var array<string, mixed> $faviconsConfig */
        $faviconsConfig = $config['favicons'];
        $builder->setParameter('spomky_labs_pwa.favicons.config', $faviconsConfig);

        /*** Service Worker ***/
        $builder->setParameter('spomky_labs_pwa.sw.enabled', $serviceWorkerConfig['enabled']);
        $builder->setParameter('spomky_labs_pwa.sw.public_url', $serviceWorkerConfig['dest'] ?? null);
        $builder->setParameter('spomky_labs_pwa.sw.config', $serviceWorkerConfig);

        /*** Resource Hints ***/
        /** @var array{enabled: bool} $resourceHintsConfig */
        $resourceHintsConfig = $config['resource_hints'] ?? [
            'enabled' => false,
        ];
        $builder->setParameter('spomky_labs_pwa.resource_hints.config', $resourceHintsConfig);

        /*** Early Hints ***/
        /** @var array{enabled: bool} $earlyHintsConfig */
        $earlyHintsConfig = $config['early_hints'] ?? [
            'enabled' => false,
        ];
        $builder->setParameter('spomky_labs_pwa.early_hints.config', $earlyHintsConfig);

        /*** Speculation Rules ***/
        /** @var array{enabled: bool} $speculationRulesConfig */
        $speculationRulesConfig = $config['speculation_rules'] ?? [
            'enabled' => false,
        ];
        $builder->setParameter('spomky_labs_pwa.speculation_rules.config', $speculationRulesConfig);

        if (! in_array($builder->getParameter('kernel.environment'), ['dev', 'test'], true)) {
            $builder->removeDefinition(PwaDevServerListener::class);
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->setAssetMapperPath($builder);
    }

    private function setAssetMapperPath(ContainerBuilder $builder): void
    {
        $path = realpath(__DIR__ . '/../assets/src');
        if ($path === false) {
            return;
        }
        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    $path => '@spomky-labs/pwa-bundle',
                ],
            ],
        ]);
    }
}
