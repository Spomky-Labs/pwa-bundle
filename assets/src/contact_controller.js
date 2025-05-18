'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    connect = async () => {
        if (!this._isSupported()) {
            this.dispatchEvent('unavailable');
        }
    }

    select = async (multiple = false) => {
        if (!this._isSupported()) {
            this.dispatchEvent('unavailable');
            return;
        }
        try {
            const contacts = await navigator.contacts.select(await navigator.contacts.getProperties(), {multiple});
            this.dispatchEvent('selection', { contacts });
        } catch (exception) {
            this.dispatchEvent('error', { exception });
        }
    }

    _isSupported = () => {
        return "contacts" in navigator;
    }
}
