<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\Dto\Shortcut;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

// Probe run in a child process by TranslatableTraitTest: it hides symfony/translation from the autoloader and
// reports what the DTOs hand over once TranslatableMessage is out of reach. A separate process is required
// because a class cannot be unloaded once the test bootstrap has pulled it in.

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
assert($loader instanceof ClassLoader);
$loader->setPsr4('Symfony\\Component\\Translation\\', []);

if (class_exists(TranslatableMessage::class)) {
    // An optimised classmap still reaches the component; there is nothing to observe here.
    echo json_encode([
        'skipped' => 'symfony/translation could not be hidden from the autoloader',
    ], \JSON_THROW_ON_ERROR);

    return;
}

$manifest = new Manifest();
$manifest->name = 'My Application';
$manifest->shortName = 'App';
$manifest->description = 'A very nice application';
$manifest->categories = ['books', 'education'];

$shortcut = new Shortcut();
$shortcut->name = "Today's agenda";
$shortcut->description = 'Events planned for today';

$screenshot = new Screenshot();
$screenshot->label = 'The home page';

echo json_encode([
    'contracts_available' => interface_exists(TranslatableInterface::class),
    'name' => $manifest->getName(),
    'short_name' => $manifest->getShortName(),
    'description' => $manifest->getDescription(),
    'categories' => $manifest->getCategories(),
    'untouched_description' => (new Manifest())->getDescription(),
    'shortcut_name' => $shortcut->getName(),
    'shortcut_description' => $shortcut->getDescription(),
    'screenshot_label' => $screenshot->getLabel(),
], \JSON_THROW_ON_ERROR);
