<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Contracts\Translation\TranslatableInterface;

final class ShareTargetParameters
{
    use TranslatableTrait;

    public null|string $title = null;

    public null|string $text = null;

    public null|string $url = null;

    /**
     * @var array<File>
     */
    public array $files = [];

    public function getTitle(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->title);
    }

    public function getText(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->text);
    }
}
