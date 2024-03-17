'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['message', 'attribute'];
    static values = {
        onlineMessage: { type: String, default: 'You are online.' },
        offlineMessage: { type: String, default: 'You are offline.' },
    };

    connect = () => {
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
        this.dispatch(name, { detail: payload });
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
