<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class OfflineFallback
{
    #[SerializedName('page')]
    public null|Url $pageFallback = null;

    #[SerializedName('image')]
    public null|Asset $imageFallback = null;

    #[SerializedName('font')]
    public null|Asset $fontFallback = null;
}
