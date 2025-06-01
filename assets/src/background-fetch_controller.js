'use strict';

import { openDB } from 'idb';
import Controller from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        dbName: { type: String, default: 'bgfetch-completed' },
    };

    async connect() {
        this.element.controller = this;
        await this.list();
    }

    async list() {
        const registration = await navigator.serviceWorker.ready;

        if (!('backgroundFetch' in registration)) {
            this.dispatchEvent('unsupported');
            return;
        }

        const ids = await registration.backgroundFetch.getIds();
        for (const id of ids) {
            const bgFetch = await registration.backgroundFetch.get(id);
            if (!bgFetch) continue;

            const detail = {
                id,
                downloaded: bgFetch.downloaded,
                downloadTotal: bgFetch.downloadTotal,
                urls: (await bgFetch.matchAll()).map(r => r.request.url),
            };

            switch (bgFetch.result) {
                case 'success':
                    break;
                case 'failure':
                    this.dispatchEvent('failed', detail);
                    break;
                default:
                    this.dispatchEvent('in-progress', detail);
                    bgFetch.addEventListener('progress', () => {
                        this.dispatchEvent('in-progress', {
                            id,
                            downloaded: bgFetch.downloaded,
                            downloadTotal: bgFetch.downloadTotal,
                        });
                    });
                    break;
            }
        }

        for (const file of await this.getStoredFiles()) {
            this.dispatchEvent('completed', file);
        }
    }

    async download({ params: { id, url, title = '', icons = [], downloadTotal } }) {
        const registration = await navigator.serviceWorker.ready;

        if (!('backgroundFetch' in registration)) {
            this.dispatchEvent('unsupported');
            return;
        }

        const existing = await registration.backgroundFetch.get(id);
        if (existing) {
            this.dispatchEvent('exists', {
                id,
                urls: existing.urls,
                title: existing.title,
                downloadTotal: existing.downloadTotal,
            });
            return;
        }

        const urls = Array.isArray(url) ? url : [url];
        const bgFetch = await registration.backgroundFetch.fetch(id, urls, { title, icons, downloadTotal });

        const bc = new BroadcastChannel('bg-fetch');
        bc.postMessage({ type: 'register-meta', id, meta: { title, icons, downloadTotal } });

        this.dispatchEvent('started', { id, urls, title, downloadTotal });

        bgFetch.addEventListener('progress', () => {
            this.dispatchEvent('in-progress', {
                id,
                downloaded: bgFetch.downloaded,
                downloadTotal: bgFetch.downloadTotal,
            });
        });
    }

    async cancel({ params: { id } }) {
        const registration = await navigator.serviceWorker.ready;
        const bgFetch = await registration.backgroundFetch.get(id);

        if (!bgFetch) {
            this.dispatchEvent('not-found', { id });
            return;
        }

        if (bgFetch.result !== '') {
            this.dispatchEvent('cancel-refused', { id, reason: 'Already finished or failed' });
            return;
        }

        await bgFetch.abort();
        this.dispatchEvent('aborted', { id });
    }

    async has({ params: { url } }) {
        const db = await this.openDb();
        return !!(await db.get('files', url));
    }

    async get(event) {
        event.preventDefault();
        const { params: { url, name = null } } = event;
        const db = await this.openDb();
        const file = await db.get('files', url);
        if (!file) {
            window.location.href = url;
            return;
        }

        const chunks = await db.getAllFromIndex('chunks', 'by-id', IDBKeyRange.only(url));
        chunks.sort((a, b) => a.index - b.index);
        const blob = new Blob(chunks.map(c => c.chunk), { type: file.contentType });

        if (name !== null) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = name || file.name;
            a.click();
            URL.revokeObjectURL(a.href);
        } else {
            window.open(URL.createObjectURL(blob), '_blank');
        }
    }

    async delete({ params: { url } }) {
        const db = await this.openDb();
        await db.delete('files', url);

        const chunks = await db.getAllFromIndex('chunks', 'by-id', IDBKeyRange.only(url));
        for (const chunk of chunks) {
            await db.delete('chunks', [chunk.id, chunk.index]);
        }
    }

    async getStoredFiles() {
        const db = await this.openDb();
        return db.getAll('files');
    }

    async openDb() {
        return openDB(this.dbNameValue, 1, {
            upgrade(db) {
                if (!db.objectStoreNames.contains('files')) {
                    db.createObjectStore('files', { keyPath: 'id' });
                }

                if (!db.objectStoreNames.contains('chunks')) {
                    const store = db.createObjectStore('chunks', { keyPath: ['id', 'index'] });
                    store.createIndex('by-id', 'id');
                }
            }
        });
    }
}
