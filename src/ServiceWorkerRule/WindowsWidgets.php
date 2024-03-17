<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class WindowsWidgets implements ServiceWorkerRule
{
    public function __construct(
        private Manifest $manifest,
        private SerializerInterface $serializer
    ) {
    }

    public function process(string $body): string
    {
        $tags = [];
        foreach ($this->manifest->widgets as $widget) {
            if ($widget->tag !== null) {
                $tags[] = $widget->tag;
            }
        }
        if (count($tags) === 0) {
            return $body;
        }
        $data = $this->serializer->serialize($tags, 'json', [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
self.addEventListener("widgetinstall", event => {
    event.waitUntil(renderWidget(event.widget));
});
async function renderWidget(widget) {
    const templateUrl = widget.definition.msAcTemplate;
    const dataUrl = widget.definition.data;
    const template = await (await fetch(templateUrl)).text();
    const data = await (await fetch(dataUrl)).text();
    await self.widgets.updateByTag(widget.definition.tag, {template, data});
}

self.addEventListener("widgetinstall", event => {
    event.waitUntil(onWidgetInstall(event.widget));
});
async function onWidgetInstall(widget) {
    const tags = await self.registration.periodicSync.getTags();
    if (!tags.includes(widget.definition.tag)) {
        await self.registration.periodicSync.register(widget.definition.tag, {
            minInterval: widget.definition.update
        });
    }
    await updateWidget(widget);
}

self.addEventListener("widgetuninstall", event => {
    event.waitUntil(onWidgetUninstall(event.widget));
});

async function onWidgetUninstall(widget) {
    if (widget.instances.length === 1 && "update" in widget.definition) {
        await self.registration.periodicSync.unregister(widget.definition.tag);
    }
}
self.addEventListener("periodicsync", async event => {
    const widget = await self.widgets.getByTag(event.tag);
    if (widget && "update" in widget.definition) {
        event.waitUntil(renderWidget(widget));
    }
});

self.addEventListener("activate", event => {
    event.waitUntil(updateWidgets());
});

async function updateWidgets() {
    const tags = {$data};
    if(!self.widgets || tags.length === 0) return;
    for (const tag of tags) {
        const widget = await self.widgets.getByTag(tag);
        if (!widget) {
            continue;
        }
        const template = await (await fetch(widget.definition.msAcTemplate)).text();
        const data = await (await fetch(widget.definition.data)).text();
        await self.widgets.updateByTag(widget.definition.tag, {template, data});
    }
}
OFFLINE_FALLBACK_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
