<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Icon
{
    public string $src;

    /**
     * @var array<int>
     */
    #[SerializedName('sizes')]
    public array $sizeList;

    public null|string $format = null;

    public null|string $purpose = null;

    #[SerializedName('sizes')]
    public function getSizeList(): string
    {
        $result = [];
        foreach ($this->sizeList as $size) {
            $result[] = $size === 0 ? 'any' : $size . 'x' . $size;
        }
        return implode(' ', $result);
    }
}
