'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    update = async ({counter}) => {
        await navigator.setAppBadge(counter);
        this.dispatchEvent('badge:updated', { counter });
    }

    clear = async () => {
        await navigator.clearAppBadge();
        this.dispatchEvent('badge:cleared');
    }
}
