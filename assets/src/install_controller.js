'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['install'];
    installPrompt = null;

    async connect() {
        this.disableInstallTargets();
        window.addEventListener("beforeinstallprompt", async (event) => {
            event.preventDefault();
            this.installPrompt = event;
            this.enableInstallTargets();
        });
    }

    async install() {
        if (!this.installPrompt) {
            return;
        }
        const result = await this.installPrompt.prompt();
        if (result.outcome === 'accepted') {
            this.disableInstallTargets();
        } else {
            this.dispatchEvent('install:cancelled');
            this.dispatchEvent('pwa:install:cancelled');
        }
    }

    enableInstallTargets() {
        this.installTargets.forEach((installElement) => {
            installElement.removeAttribute("hidden");
        });
    }

    disableInstallTargets() {
        this.installTargets.forEach((installElement) => {
            installElement.setAttribute("hidden", "");
        });
    }
}
