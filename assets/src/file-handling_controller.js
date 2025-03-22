'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    connect() {
        if (!"launchQueue" in window) {
            return;
        }
        window.launchQueue.setConsumer((launchParams) => {
            launchParams.files.forEach(async (file) => {
                const data = URL.createObjectURL(await file.getFile());
                this.dispatchEvent('pwa:file:selected', {data});
            });
        });
    }
}
