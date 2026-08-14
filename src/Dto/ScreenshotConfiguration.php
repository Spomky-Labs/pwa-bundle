<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use function array_map;
use function implode;
use InvalidArgumentException;
use function is_array;
use function is_string;
use function sprintf;

final readonly class ScreenshotConfiguration
{
    /**
     * @param array<ScreenshotDimension> $sizes
     * @param array<string, mixed> $routeParameters
     */
    public function __construct(
        public null|string $url = null,
        public null|string $route = null,
        public array $routeParameters = [],
        public string $output = '',
        public string $filename = 'screenshot',
        public array $sizes = [],
        public null|string $platform = null,
        public null|string $locale = null,
        public null|string $label = null,
        public string $format = 'png',
    ) {
    }

    public function isRoute(): bool
    {
        return $this->route !== null;
    }

    /**
     * @param array{url?: string, route?: string, parameters?: array<string, mixed>, output?: string, filename?: string, sizes?: array<string>|string, platform?: string, locale?: null|string, label?: null|string, format?: string} $data
     */
    public static function fromArray(array $data, string $defaultOutput): self
    {
        $sizesData = $data['sizes'] ?? [];
        if (is_string($sizesData)) {
            $sizesData = [$sizesData];
        }

        $sizes = [];
        $invalidSizes = [];

        foreach ($sizesData as $sizeStr) {
            $expandedSizes = ScreenshotDimension::expandFromString($sizeStr);
            if ($expandedSizes === []) {
                $invalidSizes[] = $sizeStr;
            } else {
                foreach ($expandedSizes as $size) {
                    $sizes[] = $size;
                }
            }
        }

        if ($invalidSizes !== []) {
            $suggestions = [];
            foreach ($invalidSizes as $invalid) {
                $suggestion = ScreenshotSize::getSuggestion($invalid);
                $suggestions[] = $suggestion !== null
                    ? sprintf('"%s" (did you mean "%s"?)', $invalid, $suggestion)
                    : sprintf('"%s"', $invalid);
            }
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid screenshot size(s): %s. Valid profile names: %s, or use explicit dimensions like "1920x1080"',
                    implode(', ', $suggestions),
                    implode(', ', ScreenshotSize::getAllProfileNames())
                )
            );
        }

        // Parse URL or route
        $url = $data['url'] ?? null;
        $route = $data['route'] ?? null;
        $routeParameters = [];

        if ($route !== null && is_string($route)) {
            $routeParameters = $data['parameters'] ?? [];
            if (! is_array($routeParameters)) {
                throw new InvalidArgumentException('Route parameters must be an array.');
            }
        }

        return new self(
            url: $url,
            route: $route,
            routeParameters: $routeParameters,
            output: $data['output'] ?? $defaultOutput,
            filename: $data['filename'] ?? 'screenshot',
            sizes: $sizes,
            platform: $data['platform'] ?? null,
            locale: $data['locale'] ?? null,
            label: $data['label'] ?? null,
            format: $data['format'] ?? 'png',
        );
    }

    public function validate(): null|string
    {
        if ($this->url === null && $this->route === null) {
            return 'Either "url" or "route" must be specified.';
        }

        if ($this->url !== null && $this->route !== null) {
            return 'Cannot specify both "url" and "route". Choose one.';
        }

        if ($this->sizes === []) {
            return 'At least one size must be specified.';
        }

        return null;
    }

    /**
     * @return array<array{size: ScreenshotDimension, width: int, height: int, formFactor: string}>
     */
    public function getExpandedSizes(): array
    {
        return array_map(
            static fn (ScreenshotDimension $size) => [
                'size' => $size,
                'width' => $size->getDimensions()['width'],
                'height' => $size->getDimensions()['height'],
                'formFactor' => $size->getFormFactor(),
            ],
            $this->sizes
        );
    }
}
