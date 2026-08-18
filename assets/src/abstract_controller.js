'use strict';

import { Controller } from '@hotwired/stimulus';
import { reportDeprecatedController } from './deprecation.js';

export default class extends Controller {
    component = undefined;

    // In the constructor rather than connect(): fifteen controllers override connect()
    // without calling super, so the warning would never fire for them. A derived class
    // has no choice but to call super() here.
    constructor(...args) {
        super(...args);
        reportDeprecatedController(this.identifier);
    }

    async loadLiveComponent() {
        try {
            const module = await import('@symfony/ux-live-component');
            return await module.getComponent(this.element);
        } catch {
            return null;
        }
    }

    dispatchEvent = async (name, payload = {}) => {
        if (this.component === undefined) {
            this.component = await this.loadLiveComponent();
        }
        this.dispatch(name, { detail: payload, bubbles: true });
        this.component?.emit?.(name, payload);
    }
}
