'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    component = undefined;

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
