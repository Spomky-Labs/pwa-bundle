importScripts(
    'https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js'
);

// *** Recipes ***
// You are free to change or remove any of these presets as you wish.
// See https://developer.chrome.com/docs/workbox/modules/workbox-recipes for more information.
const {
    pageCache,
    imageCache,
    googleFontsCache,
} = workbox.recipes;
const {registerRoute} = workbox.routing;
const {CacheFirst} = workbox.strategies;
const {CacheableResponsePlugin} = workbox.cacheableResponse;

pageCache();// => Cache pages with a network-first strategy.
imageCache();// => Cache images with a cache-first strategy.
googleFontsCache();// => Cache the underlying font files with a cache-first strategy.

// *** Assets ***
// Cache CSS, JS, and Web Worker requests with a cache-first strategy.
// We could use staticResourceCache();, but this strategy uses a stale-while-revalidate strategy,
// which is not ideal for static resources served by Asset Mapper (assets are immutable)
const cacheName = 'static-resources';
const matchCallback = ({request}) =>
    // CSS
    request.destination === 'style' ||
    // JavaScript
    request.destination === 'script' ||
    // Web Workers
    request.destination === 'worker';

registerRoute(
    matchCallback,
    new CacheFirst({
        cacheName,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);

// *** Bundle rules ***
//PRECACHING_PLACEHOLDER
//WARM_CACHE_URLS_PLACEHOLDER
//OFFLINE_FALLBACK_PLACEHOLDER
//WIDGETS_PLACEHOLDER
