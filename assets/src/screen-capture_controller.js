'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    getSupportedConstraints = async() => {
        try {
            this._checkSupported();
            const constraints = navigator.mediaDevices.getSupportedConstraints();
            this.dispatchEvent('pwa:screen-capture:constraints', {constraints});
        } catch (error) {
            this.dispatchEvent('pwa:screen-capture:error', {error});
        }
    }

    capture = async({params}) => {
        try {
            this._checkScreenCaptureSupported();
            const options = {
                video: params.videoConstraints ?? true,
                audio: params.audioConstraints ?? false,
                monitorTypeSurfaces: params.monitorTypeSurfaces ?? undefined,
                preferCurrentTab: params.preferCurrentTab ?? undefined,
                selfBrowserSurface: params.selfBrowserSurface ?? undefined,
                surfaceSwitching: params.surfaceSwitching ?? undefined,
                systemAudio: params.systemAudio ?? undefined,
            };
            const stream = await navigator.mediaDevices.getDisplayMedia(options);
            const tracks = stream.getTracks();
            if (tracks.length === 0) {
                throw "No tracks found";
            }
            tracks.forEach((track) => {
                track.addEventListener('ended', () => {
                    window.recorder.stop();
                });
            })

            const chunks = [];
            window.recorder = new MediaRecorder(stream);
            window.recorder.addEventListener('dataavailable', (event) => {
                if (event.data.size <= 0) {
                    return;
                }
                chunks.push(event.data);
            })

            window.recorder.addEventListener('stop', () => {
                const file = new File(chunks, 'video.mp4', {type: 'video/mp4'});
                const exportUrl  = URL.createObjectURL(file);
                this.dispatchEvent('pwa:screen-capture:available', {exportUrl});
            });
            window.recorder.addEventListener('start', () => {
                const info = tracks.map((track) => {
                    return {
                        capabilities: track.getCapabilities(),
                        settings: track.getSettings()
                    };
                });
                this.dispatchEvent('pwa:screen-capture:started', info);
            });

            window.recorder.start();
        } catch (error) {
            this.dispatchEvent('pwa:screen-capture:error', {error});
        }
    }

    _checkScreenCaptureSupported = () => {
        if (!navigator.mediaDevices.getDisplayMedia) {
            throw "Your device does not support the Screen Capture API";
        }
    }
}
