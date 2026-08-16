<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function sprintf;

/**
 * The raw bytes of a configured image source, together with the media type they were recognised as.
 */
final readonly class SourceImage
{
    public function __construct(
        public string $content,
        public string $mimeType,
    ) {
    }

    public static function create(string $content, string $mimeType): self
    {
        return new self($content, $mimeType);
    }

    public function isSvg(): bool
    {
        return $this->mimeType === 'image/svg+xml';
    }

    /**
     * The source inlined as a data URI, so that a template can reference it without the renderer having to
     * resolve any path of its own.
     */
    public function getDataUri(): string
    {
        return sprintf('data:%s;base64,%s', $this->mimeType, base64_encode($this->content));
    }
}
