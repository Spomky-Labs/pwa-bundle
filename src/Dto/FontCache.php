<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class FontCache extends Cache
{
    public bool $enabled = true;

    #[SerializedName('regex')]
    public string $regex = '/\.(ttf|eot|otf|woff2)$/';
}
