'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async share({params}) {
        const {data} = params;
        if (!data) {
            this.dispatchEvent('error', {data, error: 'No data provided.'});
            return;
        }
        try {
            if (!navigator.canShare || !navigator.canShare(data)) {
                this.dispatchEvent('error', {data, error: 'Cannot share data.'});
                return;
            }
            await navigator.share(data);
            this.dispatchEvent('success', {data});
        } catch (error) {
            this.dispatchEvent('error', {data, error});
        }
    }
}
