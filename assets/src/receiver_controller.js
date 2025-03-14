'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async connect() {
        if (!navigator.presentation.receiver) {
            return;
        }
        const list = await navigator.presentation.receiver.connectionList;
        list.connections.map((connection) => this.addConnection(connection));
    }

    addConnection(connection) {
        connection.addEventListener(
            'message',
            (event) => {
                const data = JSON.parse(event.data);
                this.dispatchEvent('pwa:receiver:message', {data});
            }
        );

        connection.addEventListener(
            'close',
            (event) => this.dispatchEvent('pwa:receiver:close', {
                connectionId: connection.connectionId,
                reason: event.reason,
                message: event.message
            })
        );
    }
}
