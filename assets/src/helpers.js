function onPeriodicSync(tag, callback) {
    const periodicChannel = new BroadcastChannel('periodic-sync');
    periodicChannel.addEventListener('message', (event) => {
        const { type, tag: receivedTag, ...data } = event.data || {};
        if (type === 'periodic-sync-update' && receivedTag === tag) {
            callback(data);
        }
    });
}

async function registerPeriodicSync(tag, minInterval, options = {}) {
    const reg = await navigator.serviceWorker.ready;
    const status = await navigator.permissions.query({
        name: 'periodic-background-sync',
    });
    if (status.state !== 'granted') {
        return;
    }

    if ('periodicSync' in reg) {
        const tags = await reg.periodicSync.getTags();
        if (!tags.includes(tag)) {
            await reg.periodicSync.register(tag, {
                minInterval,
                ...options
            });
        }
    }
}

export {
    onPeriodicSync,
    registerPeriodicSync
};
