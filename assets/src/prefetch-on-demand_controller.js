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
            "type": "CACHE_URLS",
            "payload": {
                "urlsToCache": params.urls
            }
        });
    }
}
