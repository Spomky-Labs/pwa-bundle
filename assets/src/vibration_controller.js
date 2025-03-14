'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    vibrateInterval = null;

    vibrate = async ({params}) => {
        const { sequence } = params;
        if (!sequence) {
            console.error('Vibration sequence is required.');
            return;
        }
        await navigator.vibrate(sequence);
        this.dispatchEvent('pwa:vibration:triggered', { sequence });
    }

    persistent = async ({params}) => {
        if (this.vibrateInterval !== null) {
            this.stop();
        }
        const { sequence, duration } = params;
        if (!sequence) {
            console.error('Vibration sequence is required.');
            return;
        }
        if (!duration) {
            console.error('Vibration duration is required.');
            return;
        }
        this.vibrateInterval = setInterval(() => {
            startVibrate(sequence);
        }, duration);
    }

    stop = async () => {
        if (this.vibrateInterval === null) {
            return;
        }
        clearInterval(this.vibrateInterval);
        this.vibrateInterval = null;
        this.dispatchEvent('pwa:vibration:stopped');
    }
}
