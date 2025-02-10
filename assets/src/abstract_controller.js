'use strict';

import { getComponent } from '@symfony/ux-live-component';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    component = null;
    async initialize() {
        try {
            this.component = await getComponent(this.element);
        } catch (e) {
        }
    }

    dispatchEvent = (name, payload) => {
        if  (payload === undefined) {
            payload = {};
        }
        this.dispatch(name, { detail: payload, bubbles: true });
        if (this.component) {
            this.component.emit(name, payload);
        }
    }
}
