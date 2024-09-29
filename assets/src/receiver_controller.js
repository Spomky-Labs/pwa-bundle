'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async connect() {
        if (!navigator.presentation.receiver) {
        }
        const connections = await navigator.presentation.receiver.connections;
        connections.connections.map(connection => addConnection(connection));
        connections.addEventListener(
            'connectionavailable',
            (event) => {
                const connection = event.connection;
                connection.addEventListener(
                    'message',
                    (event) => this.dispatchEvent('message', {message: event.data})
                );
                connection.addEventListener(
                    'close',
                    () => this.dispatchEvent('close')
                );
            }
        );
    }
}
