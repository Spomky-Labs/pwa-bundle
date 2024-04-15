<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Contracts\Translation\TranslatableInterface;

final class Manifest
{
    use TranslatableTrait;

    #[SerializedName('use_credentials')]
    public bool $useCredentials = true;

    #[SerializedName('background_color')]
    public null|string $backgroundColor = null;

    /**
     * @var array<string>
     */
    public array $categories = [];

    public null|string $description = null;

    public null|string $display = null;

    /**
     * @var array<string>
     */
    #[SerializedName('display_override')]
    public array $displayOverride = [];

    public null|string $id = null;

    public null|string $orientation = null;

    public null|string $dir = null;

    public null|string $lang = null;

    public null|string $name = null;

    #[SerializedName('short_name')]
    public null|string $shortName = null;

    public null|string $scope = null;

    #[SerializedName('start_url')]
    public null|string $startUrl = null;

    #[SerializedName('theme_color')]
    public null|string $themeColor = null;

    #[SerializedName('edge_side_panel')]
    public null|EdgeSidePanel $edgeSidePanel = null;

    #[SerializedName('iarc_rating_id')]
    public null|string $iarcRatingId = null;

    /**
     * @var array<ScopeExtension>
     */
    #[SerializedName('scope_extensions')]
    public array $scopeExtensions = [];

    #[SerializedName('handle_links')]
    public null|string $handleLinks = null;

    /**
     * @var array<Icon>
     */
    public array $icons = [];

    /**
     * @var array<Screenshot>
     */
    public array $screenshots = [];

    /**
     * @var array<FileHandler>
     */
    #[SerializedName('file_handlers')]
    public array $fileHandlers = [];

    #[SerializedName('launch_handler')]
    public null|LaunchHandler $launchHandler = null;

    /**
     * @var array<ProtocolHandler>
     */
    #[SerializedName('protocol_handlers')]
    public array $protocolHandlers = [];

    /**
     * @var array<RelatedApplication>
     */
    #[SerializedName('related_applications')]
    public array $relatedApplications = [];

    #[SerializedName('prefer_related_applications')]
    public bool $preferRelatedApplications = false;

    /**
     * @var array<Shortcut>
     */
    public array $shortcuts = [];

    #[SerializedName('share_target')]
    public null|ShareTarget $shareTarget = null;

    /**
     * @var array<Widget>
     */
    public array $widgets = [];

    #[SerializedName('serviceworker')]
    public null|ServiceWorker $serviceWorker = null;

    /**
     * @return array<TranslatableInterface|string>
     */
    public function getCategories(): array
    {
        return $this->provideTranslation($this->categories);
    }

    public function getDescription(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->description);
    }

    public function getName(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->name);
    }

    #[SerializedName('short_name')]
    public function getShortName(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->shortName);
    }

    #[SerializedName('start_url')]
    public function getStartUrl(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->startUrl);
    }
}
