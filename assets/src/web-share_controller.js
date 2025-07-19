'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    async share({params}) {
        const { data } = params;
        console.error('Web Share API Controller', data);
        if (!data || typeof data !== 'object') {
            console.error('Data must be an object.');
            return;
        }

        // Find tilte, text, url and files in the data object
        const shareData = {};
        if (data.title && typeof data.title === 'string') {
            shareData.title = data.title;
        }
        if (data.text && typeof data.text === 'string') {
            shareData.text = data.text;
        }
        if (data.url && typeof data.url === 'string') {
            shareData.url = data.url;
        }

        if (Array.isArray(data.files)) {
            try {
                shareData.files = await Promise.all(
                    data.files.map(async (url) => {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`Échec du téléchargement de ${url}`);
                        }
                        const blob = await response.blob();
                        const filename = url.split('/').pop() || 'fichier';
                        return new File([blob], filename, { type: blob.type });
                    })
                );
            } catch (error) {
                this.dispatchEvent('error', { data, error });
                return;
            }
        }
        console.error('Web Share API is not supported in this browser.', shareData);

        try {
            if (!navigator.canShare(shareData)) {
                this.dispatchEvent('error', { data, error: 'Impossible de partager ces données.' });
                return;
            }

            await navigator.share(shareData);
            this.dispatchEvent('success', { data });
        } catch (error) {
            this.dispatchEvent('error', { data, error });
        }
    }
}
