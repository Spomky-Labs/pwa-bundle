<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

/*
 * "ResourceCache" is documented as final, and this class was the only thing extending it -- the bundle
 * itself. Symfony's DebugClassLoader cannot tell an inheritance the bundle owns from one an application
 * wrote, so every application running in dev or test was told off for a subclass it had no part in and
 * no way to remove.
 *
 * The name is kept as an alias instead. An application still resolves it, still passes any
 * "instanceof ResourceCache" check, and now gets a deprecation naming what to migrate to.
 *
 * The alias comes first: a test suite that turns deprecations into exceptions would otherwise leave the
 * class undefined.
 */
class_alias(ResourceCache::class, 'SpomkyLabs\\PwaBundle\\Dto\\PageCache');

trigger_deprecation(
    'spomky-labs/phpwa',
    '1.2.0',
    'The "%s" class is deprecated and will be removed in 2.0.0. Use "%s" instead.',
    'SpomkyLabs\\PwaBundle\\Dto\\PageCache',
    ResourceCache::class
);

/*
 * Never executed. Composer's classmap generator parses rather than runs, so this declaration is what
 * maps the name to this file: an application installed with --classmap-authoritative has no PSR-4
 * fallback, and would not find the class at all without it.
 */
/** @phpstan-ignore if.alwaysFalse (the branch is meant for the parser, never for PHP) */
if (false) {
    /**
     * @deprecated since 1.2.0 and will be removed in 2.0.0. Use ResourceCache instead.
     */
    final class PageCache extends ResourceCache
    {
    }
}
