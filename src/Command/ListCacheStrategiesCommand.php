<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Yaml\Yaml;
use function assert;

#[AsCommand(name: 'pwa:cache:list-strategies', description: 'List the available cache strategies',)]
final class ListCacheStrategiesCommand extends Command
{
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
        $table->setHeaders(['Name', 'Strategy', 'URL pattern', 'Enabled', 'Workbox?', 'Options']);
        foreach ($this->services as $service) {
            assert($service instanceof HasCacheStrategies);
            $strategies = $service->getCacheStrategies();
            foreach ($strategies as $strategy) {
                $table->addRow([
                    $strategy->name,
                    $strategy->strategy,
                    $strategy->urlPattern,
                    $strategy->enabled ? 'Yes' : 'No',
                    $strategy->requireWorkbox ? 'Yes' : 'No',
                    Yaml::dump($strategy->options),
                ]);
            }
        }
        $table->render();

        return self::SUCCESS;
    }
}
