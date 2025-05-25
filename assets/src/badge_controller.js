'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    update = async ({params}) => {
        const { counter } = params;
        if (counter === undefined) {
            return;
        }
        await navigator.setAppBadge(counter);
        this.dispatchEvent('updated', { counter });
    }

    clear = async () => {
        await navigator.clearAppBadge();
        this.dispatchEvent('updated', { counter: 0 });
    }
}
