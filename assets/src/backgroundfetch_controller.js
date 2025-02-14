'use strict';

import Controller from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        id: { type: String },
        url: { type: String },
        cacheName: { type: String, default: null },
        persistentStorage: { type: Boolean, default: false },
        title: { type: String, default: undefined },
        downloadTotal: { type: Number, default: undefined },
        icons: { type: String, default: undefined },
    };
    cache = null;

    connect = async () => {
        if (!this.idValue) {
            console.error("No ID provided");
            return;
        }
        if (!this.urlValue) {
            console.error("No URL provided");
            return;
        }
        if (this.persistentStorageValue && navigator.storage && navigator.storage.persist) {
            navigator.storage.persist();
        }
        if (this.cacheNameValue) {
            this.cache = await caches.open(this.cacheNameValue);
        }
        await this.state();
    }

    state = async () => {
        const payload = {
            id: this.idValue,
            url: this.urlValue,
        }
        if (!this.cache) {
            this.dispatchEvent('missing', payload);
            return 'missing';
        }
        const isInCache = await this.cache.match(this.urlValue);
        if (!isInCache) {
            this.dispatchEvent('missing', payload);
            return 'missing';
        }
        this.dispatchEvent('cached', payload);
        return 'cached';
    }

    download = async ({params}) => {
        const state = await this.state();
        if (state === 'cached' && !params.force) {
            return;
        }
        const registration = await navigator.serviceWorker.ready;
        const bgFetch = await registration.backgroundFetch.fetch(this.idValue, [this.urlValue], {
            title: this.titleValue,
            icons: JSON.parse(this.iconsValue ??'[]'),
            downloadTotal: this.downloadTotalValue,
        });
        bgFetch.addEventListener('progress', () => this.dispatchStatus(bgFetch));
    }

    dispatchStatus = async (bgFetch) => {
        this.dispatchEvent('progress', {
            id: bgFetch.id,
            uploadTotal: bgFetch.uploadTotal,
            uploaded: bgFetch.uploaded,
            downloadTotal: bgFetch.downloadTotal,
            downloaded: bgFetch.downloaded,
            result: bgFetch.result,
            failureReason: bgFetch.failureReason,
            recordsAvailable: bgFetch.recordsAvailable,
        });
        if (!bgFetch.recordsAvailable || !this.cache) {
            return;
        }
        const records = await bgFetch.matchAll();
        const promises = records.map(async record => {
            const response = await record.responseReady;
            await this.cache.put(record.request, response);
            this.dispatchEvent('cached', {
                id: this.idValue,
                url: this.urlValue,
            });
        });
        await Promise.all(promises);
    }
}
