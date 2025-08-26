<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Theme
{
    public Asset $src;

    #[SerializedName('background_color')]
    public null|string $backgroundColor = null;

    /**
     * @var int<1, 50>|null
     */
    #[SerializedName('border_radius')]
    public null|int $borderRadius = null;

    /**
     * @var int<1, 100>|null
     */
    #[SerializedName('image_scale')]
    public null|int $imageScale = null;

    /**
     * @var array<string, mixed>
     */
    #[SerializedName('svg_attr')]
    public array $svgAttributes = [];
}
