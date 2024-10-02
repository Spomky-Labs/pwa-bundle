'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    dispatchEvent = (name, payload) => {
        this.dispatch(name, { detail: payload });
    }
}
