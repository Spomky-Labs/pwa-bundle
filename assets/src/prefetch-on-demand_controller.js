'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    prefetch = async ({params}) => {
        const workbox = window.workbox;
        if (!workbox || !params.urls) {
            return;
        }

        const result = await workbox.messageSW({
            "type": "CACHE_URLS",
            "payload": {
                "urlsToCache": params.urls
            }
        });
        this.dispatchEvent(
            result === true ?'prefetched': 'error',
            {params}
        );
    }

    dispatchEvent = (name, payload) => {
        this.dispatch(name, { detail: payload });
    }
}
