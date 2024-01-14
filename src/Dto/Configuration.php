<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Configuration
{
    #[SerializedName('icon_folder')]
    public string $iconFolder;

    #[SerializedName('icon_prefix_url')]
    public string $iconPrefixUrl = '';

    #[SerializedName('screenshot_folder')]
    public string $screenshotFolder;

    #[SerializedName('screenshot_prefix_url')]
    public string $screenshotPrefixUrl = '';

    #[SerializedName('manifest_filepath')]
    public string $manifestFilepath;
}
