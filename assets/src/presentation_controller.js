'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static values = {
        urls: { type: Array },
    };

    request = null;
    connection = null;
    async connect() {
        this.request = new PresentationRequest(this.urlsValue);
        const availability = await this.request.getAvailability();
        this.dispatchEvent('pwa:presentation:availability:changed', { availability });
        availability.onchange = () => {
            this.dispatchEvent('pwa:presentation:availability:changed', { availability });
        }
    }

    start = async () => {
        if (!this.request) {
            return;
        }
        const connection = await this.request.start();
        this.setConnection(connection);
    }

    reconnect = async () => {
        const connectionId = localStorage.getItem('presentation_connection_id');
        if (!connectionId) {
            return;
        }

        const connection = await this.request.reconnect(connectionId);
        this.setConnection(connection);
    }

    async send ({params}) {
        if (!this.connection) {
            return;
        }
        this.connection.send(JSON.stringify(params));
    }

    terminate = () => {
        if (!this.connection) {
            return;
        }
        const id = this.connection.id;
        this.connection.onclose = null;
        this.connection.terminate();
        this.connection = null;
        localStorage.removeItem('presentation_connection_id');
        this.dispatchEvent('pwa:presentation:terminated', {id});
    }

    setConnection(connection) {
        if (this.connection) {
            this.terminate();
        }

        this.connection = connection;
        localStorage.setItem('presentation_connection_id', connection.id);
        this.dispatchEvent('pwa:presentation:started', {id: connection.id});
    }
}
