<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class ExpectRedirectResponsePlugin implements CachePluginInterface
{
    public function render(int $jsonOptions = 0): string
    {
        return "{fetchDidSucceed: ({response}) => {if (response.type !== 'opaqueredirect' || response.redirect !== true) {throw new Error('Expected a redirect response.');}return response;}}";
    }

    public static function create(): self
    {
        return new self();
    }

    public function getName(): string
    {
        return 'ExpectRedirectResponse';
    }
}
