<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A kernel dedicated to the `*_localized` manifest members: the bundle test configuration declares no enabled
 * locale, which is exactly what the feature needs to produce anything.
 *
 * @internal
 */
final class LocalizedManifestKernel extends Kernel
{
    /**
     * @param array<string, mixed> $manifestConfig
     */
    public function __construct(
        string $name,
        private readonly array $manifestConfig,
    ) {
        parent::__construct('test_localized_' . $name, false);
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [new FrameworkBundle(), new SpomkyLabsPwaBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $manifestConfig = $this->manifestConfig;
        $loader->load(static function (ContainerBuilder $container) use ($manifestConfig): void {
            $container->register(DummyImageProcessor::class, DummyImageProcessor::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
            $container->register('asset_mapper.local_public_assets_filesystem', TestFilesystem::class)
                ->setAutowired(true);

            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'http_method_override' => true,
                'handle_all_throwables' => true,
                'assets' => [
                    'enabled' => true,
                ],
                'asset_mapper' => [
                    'paths' => [
                        'tests/images' => 'pwa',
                    ],
                ],
                'router' => [
                    'utf8' => true,
                    'resource' => '%kernel.project_dir%/tests/routes.php',
                ],
                'default_locale' => 'en',
                'enabled_locales' => ['en', 'fr', 'de', 'ar'],
                'translator' => [
                    'enabled' => true,
                    'default_path' => '%kernel.project_dir%/tests/translations',
                    'fallbacks' => ['en'],
                ],
            ]);
            $container->loadFromExtension('pwa', [
                'image_processor' => DummyImageProcessor::class,
                'manifest' => [
                    'enabled' => true,
                    ...$manifestConfig,
                ],
            ]);
        });
    }
}
