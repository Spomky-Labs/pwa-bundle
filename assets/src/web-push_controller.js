'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        applicationServerKey: { type: String },
    };

    async connect () {
        await this.status();
    }

    async status() {
        const serviceWorkerRegistration = await navigator.serviceWorker.ready;
        const subscription = await serviceWorkerRegistration.pushManager.getSubscription();
        if (!subscription) {
            this.dispatchEvent('unsubscribed');
            return;
        }

        this._dispatchSubscription(subscription);
    }

    async subscribe() {
        try {
            await this.checkNotificationPermission();
            const serviceWorkerRegistration = await navigator.serviceWorker.ready;
            const subscription = await serviceWorkerRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this._urlBase64ToUint8Array(this.applicationServerKeyValue),
            });
            if (subscription) {
                this._dispatchSubscription(subscription);
            }
        } catch (error) {
            if (Notification.permission === 'denied') {
                this.dispatchEvent('denied');
            } else {
                this.dispatchEvent('error', {error});
            }
        }
    }

    async unsubscribe() {
        try {
            const serviceWorkerRegistration = await navigator.serviceWorker.ready;
            const subscription = await serviceWorkerRegistration.pushManager.getSubscription();
            if (!subscription) {
                this.dispatchEvent('unsubscribed');
                return;
            }

            await subscription.unsubscribe();
            this.dispatchEvent('unsubscribed');
        } catch (error) {
            this.dispatchEvent('error', {error});
        }
    }

    async checkNotificationPermission() {
        if (Notification.permission === 'denied') {
            throw new Error('Push messages are blocked.');
        }

        if (Notification.permission === 'granted') {
            return;
        }

        if (Notification.permission === 'default') {
            const result = await Notification.requestPermission();
            if (result !== 'granted') {
                throw new Error('Bad permission result');
            }
            return;
        }

        throw new Error('Unknown permission');
    }

    _urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    _dispatchSubscription(subscription) {
        const supportedContentEncodings = PushManager.supportedContentEncodings || ['aesgcm'];
        console.warn({
            supportedContentEncodings,
            ...subscription.toJSON(),
        })
        this.dispatchEvent('subscribed', {
            supportedContentEncodings,
            ...subscription.toJSON(),
        });
    }
}
