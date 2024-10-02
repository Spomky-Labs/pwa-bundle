'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    connect () {
        document.addEventListener("fullscreenchange", () => {
            this.dispatchEvent('fullscreen:change', {
                fullscreen: document.fullscreenElement !== null,
                element: document.fullscreenElement
            });
        });
        document.addEventListener("fullscreenerror", () => {
            this.dispatchEvent('fullscreen:error', {
                element: document.fullscreenElement
            });
        });
    }

    request = async (event) => {
        const {params} = event;
        const {target, ...rest} = params;
        if (!target) {
            await document.documentElement.requestFullscreen(rest);
            return
        }
        const element = document.getElementById(target);
        if (!element) {
            console.error('Element not found:', target);
            return;
        }
        await element.requestFullscreen(rest);
    }

    exit = async () => {
        await document.exitFullscreen();
    }
}
