'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        params: { type: Array, default: {
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            redirect: 'follow',
            referrerPolicy: 'no-referrer'
        }},
        headers: { type: Array, default: [] },
        redirection: { type: String, default: null },
    };

    async onSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form instanceof HTMLFormElement || !form.checkValidity()) {
            return;
        }
        const url = form.action;
        const encType = form.encType ?? 'application/x-www-form-urlencoded';
        const method = form.method ?? 'POST';
        const params = this.paramsValue;
        params.headers = this.headersValue;
        params.headers['Content-Type'] = encType;

        try {
            params.body = new FormData(form);
            await fetch(url, params);
        } catch (error) {
            // No need to do anything here
        } finally {
            if (this.redirectionValue) {
                window.location.href = this.redirectionValue;
            }
        }
    }
}
