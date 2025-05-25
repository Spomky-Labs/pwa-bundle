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
        this.status();
    }

    disconnect() {
        document.removeEventListener('visibilitychange', this._handleVisibilityChange);
        this.release();
    }

    async lock() {
        if (!('wakeLock' in navigator) || this.wakeLock) {
            return;
        }

        this.wakeLock = await navigator.wakeLock.request('screen');
        this.wakeLock.addEventListener('release', () => {
            this.dispatchEvent('updated', {wakeLock: null});
            this.wakeLock = null;
        });
        this.dispatchEvent('updated', {wakeLock: this.wakeLock});
    }

    async release() {
        if (this.wakeLock) {
            this.wakeLock.release();
            this.wakeLock = null;
        }
    }

    async toggle() {
        if (this.wakeLock) {
            await this.release();
        } else {
            await this.lock();
        }
    }

    async status() {
        this.dispatchEvent('updated', {wakeLock: this.wakeLock || null});
    }

    _handleVisibilityChange = async () => {
        if (document.visibilityState === 'visible') {
            setTimeout(async () => await this.lock(), 1000);
        }
    };
}
