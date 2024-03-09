'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        urls: { type: Array, default: []}
    };

    connect = () => {
        const workbox = window.workbox;
        if (!workbox) {
            return;
        }

        workbox.messageSW({
            "type": "PREFETCH",
            "payload": {
                "urls": this.urlsValue
            }
        });
    }
}
