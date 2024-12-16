'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    watchId = null;

    locate({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('geolocation:unsupported');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {this.dispatchEvent('geolocation:position', {latitude: position.coords.latitude, longitude: position.coords.longitude});},
            (error) => {this.dispatchEvent('geolocation:error', {error: error});},
            params
        );
    }

    watch({params}) {
        if (!navigator.geolocation) {
            this.dispatchEvent('geolocation:unsupported');
            return;
        }
        if (this.watchId) {
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {this.dispatchEvent('geolocation:position', {latitude: position.coords.latitude, longitude: position.coords.longitude});},
            (error) => {this.dispatchEvent('geolocation:error', {error: error});},
            params
        );
    }

    clearWatch() {
        if (!this.watchId) {
            return;
        }

        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
        this.dispatchEvent('geolocation:watch:cleared');
    }
}
