'use strict';

import AbstractController from './abstract_controller.js';
import { signJWT } from './jwt_signer.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        params: {
            type: Object,
            default: {
                mode: 'cors',
                cache: 'no-cache',
                credentials: 'same-origin',
                redirect: 'follow',
                referrerPolicy: 'no-referrer'
            }
        },
        headers: { type: Object, default: {} },
        redirection: { type: String, default: null },
        authenticating: { type: Boolean, default: false },
        keyIdIndex: { type: String, default: 'default' },
    };

    send = async (event) => {
        const form = this.element;
        if (!(form instanceof HTMLFormElement) || !form.reportValidity()) {
            this.dispatchEvent('invalid-data');
            return;
        }
        event.preventDefault();

        const url = form.action;
        const redirectTo = this.redirectionValue;
        try {
            const params = this.paramsValue;
            params.headers = this.headersValue;
            switch (form.enctype) {
                case 'multipart/form-data':
                    delete params.headers['Content-Type'];
                    params.body = new FormData(form);
                    break;
                case 'application/json':
                    params.headers['Content-Type'] = 'application/json';
                    params.body = JSON.stringify(Object.fromEntries(new FormData(form)));
                    break;
                case 'application/x-www-form-urlencoded':
                    params.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    params.body = (new URLSearchParams(new FormData(form))).toString();
                    break;
                default:
                    this.dispatchEvent('unsupported-enctype');
                    return;
            }
            params.method = (form.method || 'GET').toUpperCase();
            if (this.authenticatingValue === true) {
                const keyIdIndex = this.keyIdIndexValue;
                const jwt = await signJWT(keyIdIndex);
                if (jwt) {
                    params.headers['Authorization'] = `Bearer ${jwt}`;
                } else {
                    this.dispatchEvent('auth-missing-key', { keyIdIndex });
                    return;
                }
            }
            this.dispatchEvent('before:send', { url, params });
            const response = await fetch(url, params);
            this.dispatchEvent('after:send', { url, params, response });
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            if (redirectTo) {
                window.location.assign(redirectTo);
            }
        } catch (error) {
            this.dispatchEvent('error', { error });
            if (redirectTo) {
                window.location.assign(redirectTo);
            }
        }
    }
}
