<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

use function count;

final readonly class ExpectStatusCodesPlugin implements CachePluginInterface
{
    /**
     * @param int[] $expectedStatusCodes
     */
    public function __construct(
        private array $expectedStatusCodes
    ) {
    }

    public function render(int $jsonOptions = 0): string
    {
        if (count($this->expectedStatusCodes) === 0) {
            return '';
        }
        $codes = implode(',', $this->expectedStatusCodes);

        return "{fetchDidSucceed: ({response}) => {if (! [{$codes}].includes(response.status)) {throw new Error('Unexpected response status code. Expected one of [{$codes}]. Got ' + response.status);}return response;}}";
    }

    /**
     * @param int[] $expectedStatusCodes
     */
    public static function create(array $expectedStatusCodes): self
    {
        return new self($expectedStatusCodes);
    }

    public function getName(): string
    {
        return 'ExpectStatusCodes';
    }
}
