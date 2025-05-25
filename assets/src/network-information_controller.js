'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
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
