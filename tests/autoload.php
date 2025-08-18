<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require_once __DIR__ . '/../vendor/autoload.php';

$phpqaPhpunitAutoload = '/tools/.composer/vendor-bin/phpunit/vendor/autoload.php';
if (file_exists($phpqaPhpunitAutoload)) {
    require_once $phpqaPhpunitAutoload;
}

ErrorHandler::register(null, false);
