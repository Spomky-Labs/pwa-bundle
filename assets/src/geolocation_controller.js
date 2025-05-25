'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    watchId = null;

    locate({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('unsupported');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {this.dispatchEvent('position', {position});},
            (error) => {this.dispatchEvent('error', {error: error});},
            params
        );
    }

    watch({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('unsupported');
            return;
        }
        if (this.watchId) {
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {this.dispatchEvent('position', {position});},
            (error) => {this.dispatchEvent('error', {error});},
            params
        );
    }

    clearWatch() {
        if (!this.watchId) {
            return;
        }

        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
        this.dispatchEvent('watch:cleared');
    }
}
