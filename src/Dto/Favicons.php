<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Favicons
{
    public bool $enabled = false;

    public Theme $default;

    public ?Theme $dark = null;

    #[SerializedName('safari_pinned_tab_color')]
    public null|string $safariPinnedTabColor = null;

    #[SerializedName('tile_color')]
    public null|string $tileColor = null;

    #[SerializedName('low_resolution')]
    public null|bool $lowResolution = null;

    #[SerializedName('use_silhouette')]
    public null|bool $useSilhouette = null;

    #[SerializedName('use_start_image')]
    public null|bool $useStartImage = null;

    public null|string $potrace = null;

    public bool $monochrome = false;
}
