'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
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
}
