'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    prefetch = ({params}) => {
        const workbox = window.workbox;
        if (!workbox || !params.urls) {
            return;
        }

        workbox.messageSW({
            "type": "PREFETCH",
            "payload": {
                "urls": params.urls
            }
        });
    }
}
