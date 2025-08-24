'use strict';

import AbstractController from './abstract_controller.js';

export default class extends AbstractController {
    static targets = ['output'];
    static values = {
        channel: { type: String },
    };

    bc = null;

    connect = () => {
        if (!this.channelValue) {
            this.dispatchEvent('error', { reason: 'No channel provided.' });
            return
        }
        this.bc = new BroadcastChannel(this.channelValue);
        this.bc.onmessage = ({data}) => this.dispatchEvent('status', data);
        this.bc.postMessage({ type: 'status-request' });
    }

    disconnect = () => {
        if (this.bc !== null) {
            this.bc.close();
        }
    }

    replay = () => {
        this.bc.postMessage({ type: 'replay-request' });
    }
}
