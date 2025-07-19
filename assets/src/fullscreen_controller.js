'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    connect () {
        document.addEventListener("fullscreenchange", () => {
            this.dispatchEvent('change', {
                fullscreen: document.fullscreenElement !== null,
                element: document.fullscreenElement
            });
        });
        document.addEventListener("fullscreenerror", () => {
            this.dispatchEvent('error', {
                element: document.fullscreenElement
            });
        });
    }

    request = async (event) => {
        const {params} = event;
        const {target, ...rest} = params;
        if (!target) {
            await document.documentElement.requestFullscreen(rest);
            return;
        }
        const element = document.querySelector(target);
        if (!element) {
            throw new Error(`Element with target "${target}" not found.`);
        }
        await element.requestFullscreen(rest);
    }

    exit = async () => {
        await document.exitFullscreen();
    }
}
