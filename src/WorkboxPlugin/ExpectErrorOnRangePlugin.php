<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class ExpectErrorOnRangePlugin implements CachePluginInterface
{
    public function __construct(
        private int $minCode,
        private int $maxCode
    ) {
    }

    public function render(int $jsonOptions = 0): string
    {
        return "{fetchDidSucceed: ({response}) => {if (response.status >= {$this->minCode} && response.status <= {$this->maxCode}) {throw new Error('Server error.');}return response;}}";
    }

    public static function create(int $minCode, int $maxCode): self
    {
        return new self($minCode, $maxCode);
    }

    public function getName(): string
    {
        return 'ExpectErrorOnRange';
    }
}
