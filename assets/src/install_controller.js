'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    deferredPrompt = null;
    isInstalled = false;

    _handleBeforeInstallPrompt = (event) => {
        event.preventDefault();
        this.deferredPrompt = event;
        this.isInstalled = false;
        this.dispatchEvent('not-installed');
    };

    _handleAppInstalled = () => {
        this.isInstalled = true;
        this.deferredPrompt = null;
        this.dispatchEvent('installed');
    };

    connect() {
        window.addEventListener('beforeinstallprompt', this._handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', this._handleAppInstalled, { once: true });
        const displayModes = ['fullscreen', 'minimal-ui', 'window-controls-overlay'];
        const isStandaloneDisplay = displayModes.some(mode =>
            window.matchMedia(`(display-mode: ${mode})`).matches
        );

        const isStandalone =
            isStandaloneDisplay ||
            window.navigator.standalone === true ||
            window.self === window.top;

        if (isStandalone) {
            this.isInstalled = true;
            this.dispatchEvent('installed');
        } else {
            this.dispatchEvent('not-installed');
        }
    }

    disconnect() {
        window.removeEventListener('beforeinstallprompt', this._handleBeforeInstallPrompt);
    }

    async install() {
        if (!this.deferredPrompt) {
            return;
        }

        this.dispatchEvent('installing');

        try {
            const result = await this.deferredPrompt.prompt();
            this.deferredPrompt = null;

            if (result.outcome !== 'accepted') {
                this.dispatchEvent('cancelled');
            }
        } catch (e) {
            this.dispatchEvent('cancelled');
        }
    }
}
