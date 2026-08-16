<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle;

use function extension_loaded;
use function in_array;
use function is_array;
use function is_string;
use SpomkyLabs\PwaBundle\CompilerPass\LoggerCompilerPass;
use SpomkyLabs\PwaBundle\CompilerPass\PreloadUrlCompilerPass;
use SpomkyLabs\PwaBundle\EventListener\PwaDevServerListener;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use function sprintf;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SpomkyLabsPwaBundle extends AbstractBundle
{
    /**
     * The built-in image processors are only registered when the PHP extension they
     * are built on is loaded (see Resources/config/services.php).
     *
     * @var array<string, string>
     */
    private const IMAGE_PROCESSOR_EXTENSIONS = [
        'pwa.image_processor.imagick' => 'imagick',
        ImagickImageProcessor::class => 'imagick',
        'pwa.image_processor.gd' => 'gd',
        GDImageProcessor::class => 'gd',
    ];

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
            $this->checkImageProcessorRequirements($imageProcessor);
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

        /* Manifest */
        $builder->setParameter('spomky_labs_pwa.manifest.enabled', $manifestConfig['enabled']);
        $builder->setParameter('spomky_labs_pwa.manifest.public_url', $manifestConfig['public_url'] ?? null);
        $builder->setParameter('spomky_labs_pwa.manifest.config', $manifestConfig);

        /* Favicons */
        /** @var array<string, mixed> $faviconsConfig */
        $faviconsConfig = $config['favicons'];
        $faviconsConfig = $this->applyManifestBackgroundColor($faviconsConfig, $manifestConfig);
        $builder->setParameter('spomky_labs_pwa.favicons.config', $faviconsConfig);

        /* Service Worker */
        $builder->setParameter('spomky_labs_pwa.sw.enabled', $serviceWorkerConfig['enabled']);
        $builder->setParameter('spomky_labs_pwa.sw.public_url', $serviceWorkerConfig['dest'] ?? null);
        $builder->setParameter('spomky_labs_pwa.sw.config', $serviceWorkerConfig);

        /* Resource Hints */
        /** @var array{enabled: bool} $resourceHintsConfig */
        $resourceHintsConfig = $config['resource_hints'] ?? [
            'enabled' => false,
        ];
        $builder->setParameter('spomky_labs_pwa.resource_hints.config', $resourceHintsConfig);

        /* Early Hints */
        /** @var array{enabled: bool} $earlyHintsConfig */
        $earlyHintsConfig = $config['early_hints'] ?? [
            'enabled' => false,
        ];
        $builder->setParameter('spomky_labs_pwa.early_hints.config', $earlyHintsConfig);

        /* Speculation Rules */
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

    /**
     * Aliasing a built-in processor whose PHP extension is missing would otherwise fail
     * much later, inside AutowirePass, with a bare "You have requested a non-existent
     * service pwa.image_processor.imagick" that points at the container instead of at
     * the missing extension.
     */
    private function checkImageProcessorRequirements(string $imageProcessor): void
    {
        $extension = self::IMAGE_PROCESSOR_EXTENSIONS[$imageProcessor] ?? null;
        if ($extension === null || extension_loaded($extension)) {
            return;
        }

        throw new InvalidConfigurationException(sprintf(
            'The image processor "%s" is configured under "pwa.image_processor" but the "%s" PHP extension is not loaded. Enable it, or select another image processor.',
            $imageProcessor,
            $extension
        ));
    }

    /**
     * The favicon themes document their background color as falling back to the manifest one. Without that
     * fallback a generated image is composited on a fully transparent background: an iOS startup image then
     * shows the application logo over whatever the system paints behind it, instead of over the color the
     * application declares.
     *
     * @param array<string, mixed> $faviconsConfig
     * @param array<string, mixed> $manifestConfig
     *
     * @return array<string, mixed>
     */
    private function applyManifestBackgroundColor(array $faviconsConfig, array $manifestConfig): array
    {
        $backgroundColor = $manifestConfig['background_color'] ?? null;
        if (! is_string($backgroundColor)) {
            return $faviconsConfig;
        }

        foreach (['default', 'dark'] as $themeName) {
            $theme = $faviconsConfig[$themeName] ?? null;
            if (! is_array($theme) || ($theme['background_color'] ?? null) !== null) {
                continue;
            }
            $theme['background_color'] = $backgroundColor;
            $faviconsConfig[$themeName] = $theme;
        }

        return $faviconsConfig;
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
