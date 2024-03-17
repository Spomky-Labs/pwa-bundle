<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategies;
use SpomkyLabs\PwaBundle\CachingStrategy\WorkboxCacheStrategy;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CachePlugin;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
     * @param iterable<HasCacheStrategies> $services
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
            $strategies = $service->getCacheStrategies();
            foreach ($strategies as $strategy) {
                if ($strategy instanceof WorkboxCacheStrategy) {
                    $table->addRow([
                        $strategy->name,
                        $strategy->strategy,
                        $strategy->matchCallback,
                        $strategy->enabled ? 'Yes' : 'No',
                        $strategy->requireWorkbox ? 'Yes' : 'No',
                        Yaml::dump(array_map(fn (CachePlugin $v): string => $v->name, $strategy->plugins)),
                        count($strategy->preloadUrls),
                        Yaml::dump($strategy->options),
                    ]);
                } else {
                    $table->addRow([
                        $strategy->name,
                        '---',
                        '---',
                        $strategy->enabled ? 'Yes' : 'No',
                        $strategy->requireWorkbox ? 'Yes' : 'No',
                        '',
                        '',
                        '',
                    ]);
                }
            }
        }
        $table->render();

        return self::SUCCESS;
    }
}
