'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async share({params}) {
        const {data} = params;
        if (!data) {
            console.error("No data provided");
            return;
        }
        try {
            if (!navigator.canShare || !navigator.canShare(data)) {
                console.error("Cannot share data");
                return;
            }
            await navigator.share(data);
            this.dispatchEvent('pwa:share:success', {data});
        } catch (error) {
            console.error("Error sharing", {error});
        }
    }
}
