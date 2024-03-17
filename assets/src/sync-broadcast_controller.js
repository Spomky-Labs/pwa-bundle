'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
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

    dispatchEvent = (name, payload) => {
        this.dispatch(name, { detail: payload });
    }

    messageReceived = async (event) => {
        const data = event.data;
        this.remainingTargets.forEach((element) => {
            element.innerHTML = data.remaining;
        });
        this.dispatchEvent('status-changed', { detail: data });
    }
}
