'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    vibrateInterval = null;

    vibrate = async ({ params }) => {
        const { pattern, interval } = params;

        if (!pattern) {
            console.error('Vibration pattern is required.');
            return;
        }

        this.stop();

        this.dispatchEvent('triggered', { pattern, interval: interval ?? null });
        await navigator.vibrate(pattern);

        if (interval) {
            this.vibrateInterval = setInterval(async () => {
                this.dispatchEvent('triggered', { pattern, interval });
                await navigator.vibrate(pattern);
            }, interval);
        }
    }

    stop = () => {
        if (this.vibrateInterval !== null) {
            clearInterval(this.vibrateInterval);
            this.vibrateInterval = null;
            this.dispatchEvent('stopped');
        }
    }
}
