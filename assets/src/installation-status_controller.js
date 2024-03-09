'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['message', 'attribute'];
    static values = {
        installedMessage: { type: String, default: 'The application is installed' },
        notInstalledMessage: { type: String, default: 'The application is not installed' },
    };

    connect = () => {
        console.log(window.matchMedia('(display-mode: standalone)').matches)
        this.dispatchEvent('connect', {});
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
    dispatchEvent = (name, payload) => {
        this.dispatch(name, { detail: payload, prefix: 'connection-status' });
    }

    statusChanged = (data) => {
        this.messageTargets.forEach((element) => {
            element.innerHTML = data.message;
        });
        this.attributeTargets.forEach((element) => {
            element.setAttribute('data-connection-status', data.status);
        });
        this.dispatchEvent('status-changed', { detail: data });
    }
}
