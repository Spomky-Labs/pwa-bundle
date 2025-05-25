'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    battery = null;

    _onBatteryChange = () => this.dispatchBatteryInfo();

    async connect() {
        this.battery = await navigator.getBattery();

        this.battery.addEventListener('chargingchange', this._onBatteryChange);
        this.battery.addEventListener('levelchange', this._onBatteryChange);
        this.battery.addEventListener('chargingtimechange', this._onBatteryChange);
        this.battery.addEventListener('dischargingtimechange', this._onBatteryChange);

        this.dispatchBatteryInfo();
    }

    disconnect() {
        if (!this.battery) return;

        this.battery.removeEventListener('chargingchange', this._onBatteryChange);
        this.battery.removeEventListener('levelchange', this._onBatteryChange);
        this.battery.removeEventListener('chargingtimechange', this._onBatteryChange);
        this.battery.removeEventListener('dischargingtimechange', this._onBatteryChange);
    }

    dispatchBatteryInfo() {
        if (!this.battery) return;
        const { charging, level, chargingTime, dischargingTime } = this.battery;
        this.dispatchEvent('updated', {
            charging,
            level,
            chargingTime,
            dischargingTime
        });
    }
}
