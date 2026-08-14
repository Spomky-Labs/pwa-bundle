<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveDeadInstanceOfAssertRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Symfony72\Rector\StmtsAwareInterface\PushRequestToRequestStackConstructorRector;
use Rector\Symfony\Symfony73\Rector\Class_\CommandHelpToAttributeRector;
use Rector\ValueObject\PhpVersion;

$builder = RectorConfig::configure();
if (file_exists('/tools/.composer/vendor-bin/phpunit/vendor/autoload.php')) {
    $builder->withAutoloadPaths(['/tools/.composer/vendor-bin/phpunit/vendor/autoload.php']);
}
$builder->withSets([
    SetList::DEAD_CODE,
    LevelSetList::UP_TO_PHP_82,
    DoctrineSetList::DOCTRINE_CODE_QUALITY,
    DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
]);
$builder->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true);
$builder->withPhpVersion(PhpVersion::PHP_82);
$builder->withPaths(
    [
        __DIR__ . '/../src',
        __DIR__ . '/../tests',
        __DIR__ . '/../castor.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ]
);
$builder->withSkip([
    PreferPHPUnitThisCallRector::class,
    // Rector only sees the highest installed Symfony version, where these assertions are
    // redundant. The bundle still supports Symfony 6.4 and 7.x, whose signatures are wider.
    RemoveDeadInstanceOfAssertRector::class,
    // Symfony 8.1 moved the bundle classes to the DependencyInjection component. The new FQCNs
    // do not exist on Symfony 6.4 and 7.x, which the bundle still supports.
    RenameClassRector::class => [
        __DIR__ . '/../tests/AppKernel.php',
        __DIR__ . '/../tests/LocalizedManifestKernel.php',
    ],
    // The $requests argument of RequestStack::__construct() only exists from Symfony 7.2 on, while
    // composer.json allows ^7.0. Applying this would break the --prefer-lowest test job.
    PushRequestToRequestStackConstructorRector::class,
    // The $help argument of the AsCommand attribute only exists from Symfony 7.3 on, while
    // composer.json allows ^6.4. Instantiating the attribute would then fail with an unknown
    // named parameter, so the help stays in configure() through setHelp().
    CommandHelpToAttributeRector::class,
    // Rector sees the PHPUnit composer resolves, currently 13, while the test job runs the phpunit-11
    // the phpqa image ships. Renaming an assertion to its later spelling — expectExceptionMessage() to
    // expectExceptionMessageIsOrContains(), added in PHPUnit 12 — passes here and fails there.
    RenameMethodRector::class => [__DIR__ . '/../tests'],
    // "PageCache" is an alias of a class it declares deprecated, so it has to name itself. Written as
    // a ::class constant, PHPStan reports the file for referencing the very class it deprecates.
    StringClassNameToClassConstantRector::class => [__DIR__ . '/../src/Dto/PageCache.php'],
]);
$builder->withParallel();
$builder->withImportNames();

return $builder;
