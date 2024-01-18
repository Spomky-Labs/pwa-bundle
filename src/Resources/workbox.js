importScripts(
    'https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js'
);

const {
    pageCache, // Cache pages with a network-first strategy.
    imageCache, // Cache images with a cache-first strategy.
    staticResourceCache, // Cache CSS, JS, and Web Worker requests with a cache-first strategy for 1 year.
    offlineFallback, // Serve an offline fallback page when the user is offline and try to revalidate the request when the user is online.
    warmStrategyCache, // Warm the cache with URLs that are likely to be visited next or during offline navigation.
} = workbox.recipes;
const { CacheFirst } = workbox.strategies;
const { precacheAndRoute } = workbox.precaching;
const { registerRoute } = workbox.routing;
const { CacheableResponsePlugin } = workbox.cacheableResponse;
const { ExpirationPlugin } = workbox.expiration;

const PAGE_CACHE_NAME = 'pages';
const FONT_CACHE_NAME = 'fonts';
const STATIC_CACHE_NAME = 'assets';
const IMAGE_CACHE_NAME = 'images';
const OFFLINE_URI = '/offline'; // URI of the offline fallback page.
const warmCacheUrls = [ // URLs to warm the cache with.
    '/',
];

// *** Recipes ***
// Cache pages with a network-first strategy.
pageCache({
    cacheName: PAGE_CACHE_NAME
});
// Cache CSS, JS, and Web Worker requests with a cache-first strategy.
staticResourceCache({
    cacheName: STATIC_CACHE_NAME,
});
// Cache images with a cache-first strategy.
imageCache({
    cacheName: IMAGE_CACHE_NAME,
    maxEntries: 60, // Default 60 images
    maxAgeSeconds: 60 * 60 * 24 * 30, // Default 30 days
});
// Serve an offline fallback page when the user is offline and try to revalidate the request when the user is online.
offlineFallback({
    pageFallback: OFFLINE_URI,
});

// Cache the underlying font files with a cache-first strategy.
registerRoute(
    ({request}) => request.destination === 'font',
    new CacheFirst({
        cacheName: FONT_CACHE_NAME,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200],
            }),
            new ExpirationPlugin({
                maxAgeSeconds: 60 * 60 * 24 * 365,
                maxEntries: 30,
            }),
        ],
    }),
);

// This directive will be compiled and populated with asset routes and revisions
// At the moment, only static assets served by Asset Mapper are listed.
precacheAndRoute(self.__WB_MANIFEST);

// Warm the cache with URLs that are likely to be visited next or during offline navigation.
const strategy = new CacheFirst();
warmStrategyCache({urls: warmCacheUrls, strategy});
