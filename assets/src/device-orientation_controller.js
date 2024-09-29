'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    connect() {
        window.addEventListener(
            'deviceorientation',
            (event) => {
                this.dispatchEvent({
                    absolute: event.absolute,
                    alpha: event.alpha,
                    beta: event.beta,
                    gamma: event.gamma
                })
            },
            true
        );
    }
}
