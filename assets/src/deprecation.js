'use strict';

const reported = new Set();

/**
 * Warns that a Stimulus controller shipped by the bundle is on its way out.
 *
 * Reported once per controller identifier: a page mounting the same controller on twenty
 * elements should not print the same line twenty times.
 *
 * @param {string|undefined} identifier
 */
export const reportDeprecatedController = (identifier) => {
    const name = identifier || 'pwa';
    if (reported.has(name)) {
        return;
    }
    reported.add(name);

    console.warn(
        `[pwa-bundle] The "${name}" Stimulus controller is deprecated since 1.6.0 and will be removed in 2.0.0. ` +
        'Copy the controller into your own application and register it there. ' +
        'See https://github.com/Spomky-Labs/pwa-bundle/issues/372#issuecomment-5295710299'
    );
};

/**
 * Same for the page-side helpers exported by the package.
 *
 * @param {string} name
 * @param {string} replacement
 */
export const reportDeprecatedHelper = (name, replacement) => {
    if (reported.has(name)) {
        return;
    }
    reported.add(name);

    console.warn(
        `[pwa-bundle] ${name}() is deprecated since 1.6.0 and will be removed in 2.0.0. ${replacement} ` +
        'See https://github.com/Spomky-Labs/pwa-bundle/issues/372#issuecomment-5295710299'
    );
};
