importScripts(
    'https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js'
);

// *** Recipes ***
// You are free to change or remove any of these presets as you wish.
// See https://developer.chrome.com/docs/workbox/modules/workbox-recipes for more information.
const {
    pageCache,
    imageCache,
    staticResourceCache,
    googleFontsCache,
} = workbox.recipes;

pageCache();// => Cache pages with a network-first strategy.
staticResourceCache();// => Cache CSS, JS, and Web Worker requests with a cache-first strategy.
imageCache();// => Cache images with a cache-first strategy.
googleFontsCache();// => Cache the underlying font files with a cache-first strategy.

// *** Bundle rules ***
//PRECACHING_PLACEHOLDER
//WARM_CACHE_URLS_PLACEHOLDER
//OFFLINE_FALLBACK_PLACEHOLDER
//WIDGETS_PLACEHOLDER
