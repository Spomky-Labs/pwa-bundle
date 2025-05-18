'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        throttle: { type: Number, default: 1000 },
    };

    async connect() {
        if (typeof DeviceMotionEvent === 'undefined') {
            this.dispatchEvent('pwa:device:motion:unavailable');
            return;
        }
        if (typeof DeviceMotionEvent.requestPermission === 'function') {
            try {
                const permissionState = await DeviceMotionEvent.requestPermission();
                if (permissionState === 'granted') {
                    window.addEventListener('devicemotion', this.dispatchMotionEvent, true);
                } else {
                    this.dispatchEvent('pwa:device:motion:permission:denied')
                }
            } catch (error) {
                this.dispatchEvent('pwa:device:motion:permission:denied')
            }
        } else {
            window.addEventListener('devicemotion', this.dispatchMotionEvent, true);
        }
    }

    disconnect() {
        // Nettoyage de l'écouteur au cas où le contrôleur se déconnecte
        window.removeEventListener('devicemotion', this.dispatchMotionEvent, true);
    }

    dispatchMotionEvent = (event) => {
        this.dispatchEvent('pwa:device:motion', {
            acceleration: {
                x: event.acceleration.x,
                y: event.acceleration.y,
                z: event.acceleration.z
            },
            accelerationIncludingGravity: {
                x: event.accelerationIncludingGravity.x,
                y: event.accelerationIncludingGravity.y,
                z: event.accelerationIncludingGravity.z
            },
            rotationRate: {
                alpha: event.rotationRate.alpha,
                beta: event.rotationRate.beta,
                gamma: event.rotationRate.gamma,
            },
            interval: event.interval,
        });
    };
}
