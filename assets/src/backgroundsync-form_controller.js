'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
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
    };

    send = async (event) => {
        event.preventDefault();
        const form = this.element;
        if (!form instanceof HTMLFormElement || !form.checkValidity()) {
            return;
        }

        const url = form.action;
        const redirectTo = this.redirectionValue || url;
        try {
            const params = this.paramsValue;
            params.headers = this.headersValue;
            if (form.enctype === 'multipart/form-data') {
                params.body = new FormData(form);
            } else if (form.enctype === 'application/json') {
                params.body = JSON.stringify(Object.fromEntries(new FormData(form)));
            } else if (form.enctype === 'application/x-www-form-urlencoded') {
                params.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                params.body = new URLSearchParams(new FormData(form));
            } else {
                // Unsupported form enctype
                return;
            }
            params.method = form.method.toUpperCase();
            const response = await fetch(url, params);
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            if (redirectTo !== undefined) {
                window.location.assign(redirectTo);
            }
        } catch (error) {
            if (redirectTo !== undefined) {
                window.location.assign(redirectTo);
            }
        }
    }
}
