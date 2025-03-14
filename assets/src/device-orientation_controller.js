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

        const dispatchOrientationEvent = (event) => {
            this.dispatchEvent('pwa:device:orientation', {
                absolute: event.absolute,
                alpha: event.alpha,
                beta: event.beta,
                gamma: event.gamma,
            });
        };

        const throttledDispatch = throttle(dispatchOrientationEvent.bind(this), this.throttleValue);
        window.addEventListener('deviceorientation', throttledDispatch, true);
    }
}
