'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    registration = null;
    async connect() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        this.registration = await navigator.serviceWorker.ready;

        if (registration.waiting) {
            this.dispatchEvent('update-available', { registration });
        }

        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;

            newWorker?.addEventListener('statechange', () => {
                if (
                    newWorker.state === 'installed' &&
                    navigator.serviceWorker.controller
                ) {
                    this.dispatchEvent('update-available', { registration });
                }
            });
        });
    }

    async update() {
        if (!this.registration?.waiting) {
            return;
        }

        this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    }
}
