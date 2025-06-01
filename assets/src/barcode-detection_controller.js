'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        formats: {
            type: Array,
            default: []
        },
    };

    barcodeDetector = null;

    connect() {
        if ('BarcodeDetector' in window) {
            this.initDetector();
        } else {
            this.dispatchEvent('unsupported');
        }
    }

    async initDetector() {
        const formats = this.formatsValue.length === 0
            ? await window.BarcodeDetector.getSupportedFormats()
            : this.formatsValue;

        this.barcodeDetector = new window.BarcodeDetector({ formats });
    }

    async detect(event) {
        if (this.barcodeDetector === null) {
            this.dispatchEvent('unsupported');
            return;
        }
        const target = event.params.target || event.target || null;
        if(!target) {
            this.dispatchEvent('error', {error: 'Invalid target'})
        }

        const barcodes = await this.barcodeDetector.detect(target);
        this.dispatchEvent('detected', barcodes);
    }
}
