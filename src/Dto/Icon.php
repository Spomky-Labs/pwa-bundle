<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Icon
{
    public Asset $src;

    /**
     * @var array<int>
     */
    #[SerializedName('sizes')]
    public array $sizeList;

    public null|string $type = null;

    public null|string $format = null;

    #[SerializedName('border_radius')]
    public null|int $borderRadius = null;

    #[SerializedName('image_scale')]
    public null|int $imageScale = null;

    #[SerializedName('background_color')]
    public null|string $backgroundColor = null;

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
