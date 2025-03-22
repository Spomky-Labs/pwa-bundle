'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    component = null;

    async initialize() {
        this.component = await this.loadLiveComponent();
    }

    async loadLiveComponent() {
        try {
            const module = await import('@symfony/ux-live-component');
            return await module.getComponent(this.element);
        } catch {
            return null;
        }
    }

    dispatchEvent = (name, payload = {}) => {
        this.dispatch(name, { detail: payload, bubbles: true });
        this.component?.emit?.(name, payload);
    }
}
