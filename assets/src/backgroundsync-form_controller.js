'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        params: { type: Object, default: {
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            redirect: 'follow',
            referrerPolicy: 'no-referrer'
        }},
        headers: { type: Object, default: {} },
        redirection: { type: String, default: null },
    };

    async onSubmit(event) {
        event.preventDefault();
        const form = this.element;
        if (!form instanceof HTMLFormElement || !form.checkValidity()) {
            console.error('Form is not valid', form, form.checkValidity());
            return;
        }
        console.error(form.method);

        const url = form.action;
        try {
            const params = this.paramsValue;
            params.headers = this.headersValue;
            params.headers['Content-Type'] = form.encType ?? 'application/x-www-form-urlencoded';
            params.body = new FormData(form);
            params.method = form.method.toUpperCase();
            await fetch(url, params);
        } catch (error) {
            console.error('Error while sending form', error);
            // No need to do anything here
        } finally {
            console.log('finally', this.redirectionValue);
            window.location.href = this.redirectionValue || url;
        }
    }
}
