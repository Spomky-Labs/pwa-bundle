//WORKBOX_IMPORT_PLACEHOLDER
const {
    pageCache,
    imageCache,
    googleFontsCache,
} = workbox.recipes;
const {registerRoute} = workbox.routing;
const {CacheFirst} = workbox.strategies;
const {CacheableResponsePlugin} = workbox.cacheableResponse;

pageCache();
imageCache();
googleFontsCache();

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

//PRECACHING_PLACEHOLDER
//WARM_CACHE_URLS_PLACEHOLDER
//OFFLINE_FALLBACK_PLACEHOLDER
//WIDGETS_PLACEHOLDER
