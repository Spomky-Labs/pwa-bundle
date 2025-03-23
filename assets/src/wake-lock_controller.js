'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        lockOnVisible: { type: Boolean, default: false },
    };
    wakeLock = null;

    async connect() {
        if (this.lockOnVisibleValue === true) {
            this.lock();
            document.addEventListener('visibilitychange', this._handleVisibilityChange);
        }
    }

    async lock() {
        this.wakeLock = await navigator.wakeLock.request('screen');
        this.wakeLock.addEventListener('release', () => {
            this.dispatchEvent('pwa:wake-lock:released');
            this.wakeLock = null;
        });
        this.dispatchEvent('pwa:wake-lock:active', {wakeLock: this.wakeLock});
    }

    async release() {
        if (this.wakeLock) {
            this.wakeLock.release();
        }
    }

    _handleVisibilityChange = async () => {
        if (document.visibilityState === 'visible') {
            setTimeout(async () => {
                await this.lock();
            }, 1000);
        }
    };
}
