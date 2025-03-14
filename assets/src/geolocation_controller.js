'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    watchId = null;

    locate({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('pwa:geolocation:unsupported');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {this.dispatchEvent('pwa:geolocation:position', {position});},
            (error) => {this.dispatchEvent('pwa:geolocation:error', {error: error});},
            params
        );
    }

    watch({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('pwa:geolocation:unsupported');
            return;
        }
        if (this.watchId) {
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {this.dispatchEvent('pwa:geolocation:position', {position});},
            (error) => {this.dispatchEvent('pwa:geolocation:error', {error});},
            params
        );
    }

    clearWatch() {
        if (!this.watchId) {
            return;
        }

        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
        this.dispatchEvent('pwa:geolocation:watch:cleared');
    }
}
