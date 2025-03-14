'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['message', 'attribute'];
    static values = {
        onlineMessage: { type: String, default: 'You are online.' },
        offlineMessage: { type: String, default: 'You are offline.' },
    };

    connect = () => {
        this.dispatchEvent('pwa:connection-status:connect', {});
        if (navigator.onLine) {
            this.statusChanged({
                status: 'ONLINE',
                message: this.onlineMessageValue,
            });
        } else {
            this.statusChanged({
                status: 'OFFLINE',
                message: this.offlineMessageValue,
            });
        }

        window.addEventListener('online', () => {
            this.statusChanged({
                status: 'ONLINE',
                message: this.onlineMessageValue,
            });
        });
        window.addEventListener('offline', () => {
            this.statusChanged({
                status: 'OFFLINE',
                message: this.offlineMessageValue,
            });
        });
    }

    statusChanged = (data) => {
        this.messageTargets.forEach((element) => {
            element.innerHTML = data.message;
        });
        this.attributeTargets.forEach((element) => {
            element.setAttribute('data-connection-status', data.status);
        });
        this.dispatchEvent('pwa:connection-status:changed', { detail: data });
    }
}
