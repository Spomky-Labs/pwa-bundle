'use strict';

import { Controller } from '@hotwired/stimulus';

enum Status {
    OFFLINE = 'OFFLINE',
    ONLINE = 'ONLINE',
}
export default class extends Controller {
    static targets = ['message', 'attribute'];
    static values = {
        onlineMessage: { type: String, default: 'You are online.' },
        offlineMessage: { type: String, default: 'You are offline.' },
    };

    declare readonly onlineMessageValue: string;
    declare readonly offlineMessageValue: string;
    declare readonly attributeTargets: HTMLElement[];
    declare readonly messageTargets: HTMLElement[];

    connect() {
        this.dispatchEvent('connect', {});
        if (navigator.onLine) {
            this.statusChanged({
                status: Status.ONLINE,
                message: this.onlineMessageValue,
            });
        } else {
            this.statusChanged({
                status: Status.OFFLINE,
                message: this.offlineMessageValue,
            });
        }

        window.addEventListener('offline', () => {
            this.statusChanged({
                status: Status.OFFLINE,
                message: this.offlineMessageValue,
            });
        });
        window.addEventListener('online', () => {
            this.statusChanged({
                status: Status.ONLINE,
                message: this.onlineMessageValue,
            });
        });
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'connection-status' });
    }

    statusChanged(data) {
        console.log('statusChanged', data);
        this.messageTargets.forEach((element) => {
            element.innerHTML = data.message;
        });
        this.attributeTargets.forEach((element) => {
            element.setAttribute('data-connection-status', data.status);
        });
        this.dispatchEvent('status-changed', { detail: data });
    }
}
