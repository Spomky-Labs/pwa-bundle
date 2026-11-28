'use strict';

import { Controller } from '@hotwired/stimulus';
import { reportDeprecatedController } from './deprecation.js';

// The only controller not extending AbstractController, so it carries the notice itself.
export default class extends Controller {
  constructor(...args) {
    super(...args);
    reportDeprecatedController(this.identifier);
  }

  connect() {
    const connection = navigator.connection;
    connection.addEventListener('change', this.updateConnectionStatus);
    this.updateConnectionStatus();
  }

  updateConnectionStatus = () => {
    const connection = navigator.connection;
    this.dispatch('change', {bubble: true, details: {connection}});
  }
}
