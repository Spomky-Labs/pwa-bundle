<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Screenshot
{
    public null|string $src = null;

    public null|string $path = null;

    public null|int $height = null;

    public null|int $width = null;

    #[SerializedName('form_factor')]
    public null|string $formFactor = null;

    public null|string $label = null;

    public null|string $platform = null;

    public null|string $format = null;
}
