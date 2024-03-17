<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ImageCache extends Cache
{
    public bool $enabled = true;

    #[SerializedName('regex')]
    public string $regex = '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/';
}
