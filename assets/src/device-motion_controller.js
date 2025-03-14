'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        throttle: { type: Number, default: 1000 },
    };

    connect() {
        const throttle = (func, limit) => {
            let inThrottle;
            return function() {
                const context = this;
                if (!inThrottle) {
                    func.apply(context, arguments);
                    inThrottle = true;
                    setTimeout(() => (inThrottle = false), limit);
                }
            };
        };

        const dispatchMotionEvent = (event) => {
            this.dispatchEvent('pwa:device:motion', {
                acceleration: event.acceleration,
                accelerationIncludingGravity: event.accelerationIncludingGravity,
                rotationRate: event.rotationRate,
                interval: event.interval,
            });
        };

        const throttledDispatch = throttle(dispatchMotionEvent.bind(this), this.throttleValue);
        window.addEventListener('devicemotion', throttledDispatch, true);
    }
}
