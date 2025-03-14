'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        channel: { type: String },
    };
    static targets = ['remaining'];

    bc = null;

    connect = () => {
        if (!this.channelValue) {
            throw new Error('The channel value is required.');
        }
        this.bc = new BroadcastChannel(this.channelValue);
        this.bc.onmessage = this.messageReceived;
    }

    disconnect = () => {
        if (this.bc !== null) {
            this.bc.close();
        }
    }

    messageReceived = async (event) => {
        const data = event.data;
        this.remainingTargets.forEach((element) => {
            element.innerHTML = data.remaining;
        });
        this.dispatchEvent('pwa:sync-broadcast:status-changed', { detail: data });
    }
}
