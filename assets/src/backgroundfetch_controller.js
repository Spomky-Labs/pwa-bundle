'use strict';

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    download = async ({params}) => {
        const { id, requests, title, icons, downloadTotal } = params;
        if (!requests) {
            return;
        }
        const processId = id || self.crypto.randomUUID();
        await this.fetch(processId, requests, {
            title,
            icons,
            downloadTotal
        });
    }

    fetch = async (id, requests, options) => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const bgFetch = await registration.backgroundFetch.fetch(id, requests, options);
            bgFetch.addEventListener('progress', () => this.dispatchStatus(bgFetch));

        } catch (error) {
            this.dispatchEvent('error', { error });
        }
    }

    status = async () => {
        const registration = await navigator.serviceWorker.ready;
        const ids = await registration.backgroundFetch.getIds();
        this.dispatchEvent('ids', ids);
        const promises = ids.map(async (id) => {
            const bgFetch = await registration.backgroundFetch.get(id);
            if (!bgFetch) {
                return;
            }
            bgFetch.addEventListener('progress', () => this.dispatchStatus(bgFetch));
        });
        await Promise.all(promises);
    }

    dispatchStatus = (bgFetch) => {
        this.dispatchEvent('status', {
            id: bgFetch.id,
            uploadTotal: bgFetch.uploadTotal,
            uploaded: bgFetch.uploaded,
            downloadTotal: bgFetch.downloadTotal,
            downloaded: bgFetch.downloaded,
            result: bgFetch.result,
            failureReason: bgFetch.failureReason,
            recordsAvailable: bgFetch.recordsAvailable,
        });
    }

    dispatchEvent = (name, payload) => {
        this.dispatch(name, { detail: payload });
    }
}
