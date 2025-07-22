<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $config): void {
    $config->sets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_82,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_ORM_214,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    $config->phpVersion(PhpVersion::PHP_82);
    $config->paths(
        [
            __DIR__ . '/../src',
            __DIR__ . '/../tests',
            __DIR__ . '/../castor.php',
            __DIR__ . '/ecs.php',
            __DIR__ . '/rector.php',
        ]
    );
    $config->rule(ExplicitNullableParamTypeRector::class);
    $config->skip([
        RemoveEmptyClassMethodRector::class => [__DIR__ . '/tests/Controller/'],
    ]);
    $config->parallel();
    $config->importNames();
    $config->importShortClasses();
};
