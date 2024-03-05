'use strict';

import {Application, Controller} from '@hotwired/stimulus';
import {getByTestId, waitFor} from '@testing-library/dom';
import {clearDOM, mountDOM} from '@symfony/stimulus-testing';
import StatusController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('pwa-status:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application: Application = Application.start();
    application.register('check', CheckController);
    application.register('pwa-status', StatusController);
};

describe('StatusController', () => {
    let container: any;

    beforeEach(() => {
        container = mountDOM(`
            <html lang="en">
                <head>
                    <title>Symfony UX</title>
                </head>
                <body>
                    <form
                        data-testid="pwa-status"
                        data-controller="check pwa-status"
                    >
                    </form>
                </body>
            </html>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        expect(getByTestId(container, 'pwa-status')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'pwa-status')).toHaveClass('connected'));
    });
});
