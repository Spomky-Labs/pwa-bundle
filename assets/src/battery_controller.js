'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async connect() {
        const battery = await navigator.getBattery();
        battery.addEventListener('chargingchange', () => this.updateChargeInfo(battery));
        battery.addEventListener('levelchange', () => this.updateLevelInfo(battery));
        battery.addEventListener('chargingtimechange', () => this.updateChargingInfo(battery));
        battery.addEventListener('dischargingtimechange', () => this.updateDischargingInfo(battery));

        await this.updateChargeInfo(battery);
        await this.updateLevelInfo(battery);
        await this.updateChargingInfo(battery);
        await this.updateDischargingInfo(battery);
    }
    update = async ({counter}) => {
        await navigator.setAppBadge(counter);
        this.dispatchEvent('battery:updated', { counter });
    }

    updateChargeInfo = async (battery) => {
        this.dispatchEvent('battery:charge', { charging: battery.charging });
    }
    updateLevelInfo = async (battery) => {
        this.dispatchEvent('battery:level', { level: battery.level });
    }
    updateChargingInfo = async (battery) => {
        this.dispatchEvent('battery:chargingtime', { chargingTime: battery.chargingTime });
    }
    updateDischargingInfo = async (battery) => {
        this.dispatchEvent('battery:dischargingtime', { dischargingTime: battery.dischargingTime });
    }
}
