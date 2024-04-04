<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\CachingStrategy\CacheStrategyInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\WorkboxCacheStrategy;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CachePluginInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Yaml\Yaml;
use function count;

#[AsCommand(name: 'pwa:cache:list-strategies', description: 'List the available cache strategies',)]
final class ListCacheStrategiesCommand extends Command
{
    /**
     * @param iterable<HasCacheStrategiesInterface> $services
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.cache_strategy')]
        private readonly iterable $services,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Cache Strategies');

        $table = $io->createTable();
        $table->setHeaders(
            ['Name', 'Strategy', 'URL pattern', 'Enabled', 'Workbox?', 'Plugins', 'Preload URLs', 'Options']
        );
        foreach ($this->services as $service) {
            foreach ($service->getCacheStrategies() as $strategy) {
                $this->processStrategy($strategy, $table);
            }
        }
        $table->render();

        return self::SUCCESS;
    }

    private function processStrategy(CacheStrategyInterface $strategy, Table $table): void
    {
        if ($strategy instanceof WorkboxCacheStrategy) {
            $table->addRow([
                $strategy->getName(),
                $strategy->strategy,
                $strategy->matchCallback,
                $strategy->isEnabled() ? 'Yes' : 'No',
                $strategy->needsWorkbox() ? 'Yes' : 'No',
                Yaml::dump(array_map(fn (CachePluginInterface $v): string => $v->getName(), $strategy->getPlugins())),
                count($strategy->getPreloadUrls()),
                Yaml::dump($strategy->getOptions()),
            ]);
        } else {
            $table->addRow([
                $strategy->getName(),
                '---',
                '---',
                $strategy->isEnabled() ? 'Yes' : 'No',
                $strategy->needsWorkbox() ? 'Yes' : 'No',
                '',
                '',
                '',
            ]);
        }
    }
}
