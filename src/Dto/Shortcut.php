<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Shortcut
{
    public string $name;

    #[SerializedName('short_name')]
    public null|string $shortName = null;

    public null|string $description = null;

    public string $url;

    /**
     * @var array<string, mixed>
     */
    #[SerializedName('url_params')]
    public array $urlParameters = [];

    /**
     * @var array<Icon>
     */
    public array $icons = [];
}
