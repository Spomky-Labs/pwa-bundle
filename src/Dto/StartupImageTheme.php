<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class StartupImageTheme
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
     * @var array<string, bool|string>
     */
    #[SerializedName('svg_attr')]
    public array $svgAttributes = [];

    /**
     * The free-form variables handed to the template, already merged with the ones declared for every
     * color scheme.
     *
     * @var array<string, mixed>
     */
    public array $context = [];
}
