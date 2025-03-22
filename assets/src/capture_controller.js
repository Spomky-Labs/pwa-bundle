'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['destination', 'download', 'element', 'region'];

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
        this.downloadTargets.forEach((target) => {
            target.setAttribute('hidden', '');
        });
        try {
            this._checkScreenCaptureSupported();
            const controller = new CaptureController();
            if (params.focusBehavior) {
                controller.setFocusBehavior(params.focusBehavior);
            }
            const options = {
                video: params.videoConstraints ?? true,
                audio: params.audioConstraints ?? false,
                monitorTypeSurfaces: params.monitorTypeSurfaces ?? undefined,
                preferCurrentTab: params.preferCurrentTab ?? undefined,
                selfBrowserSurface: params.selfBrowserSurface ?? undefined,
                surfaceSwitching: params.surfaceSwitching ?? undefined,
                systemAudio: params.systemAudio ?? undefined,
                controller
            };
            const stream = await navigator.mediaDevices.getDisplayMedia(options);
            const tracks = stream.getTracks();
            if (tracks.length === 0) {
                throw "No tracks found";
            }
            tracks.forEach((track) => {
                track.addEventListener('ended', () => {
                    this.dispatchEvent('pwa:screen-capture:track:ended', track);
                    window.recorder.stop();
                });
            })
            const videoTracks = tracks.filter((track) => track.kind === 'video');
            console.log(
                videoTracks,
                this.hasElementTarget,
                this.hasRegionTarget
            );
            if (this.hasElementTarget) {
                const restrictionTarget = await RestrictionTarget.fromElement(this.elementTarget);
                await videoTracks[0].restrictTo(restrictionTarget);
            }
            if (this.hasRegionTarget) {
                const cropTarget = await CropTarget.fromElement(this.regionTarget);
                await videoTracks[0].cropTo(cropTarget);
            }

            window.recorder = new MediaRecorder(stream);
            if (this.downloadTargets.length !== 0) {
                const chunks = [];
                window.recorder.addEventListener('dataavailable', (event) => {
                    if (event.data.size <= 0) {
                        return;
                    }
                    chunks.push(event.data);
                });
                window.recorder.addEventListener('stop', () => {
                    const file = new File(chunks, 'video.mp4', {type: 'video/mp4'});
                    const exportUrl  = URL.createObjectURL(file);
                    this.dispatchEvent('pwa:screen-capture:available', {exportUrl});
                    this.downloadTargets.forEach((target) => {
                        target.href = exportUrl;
                        target.download = 'video.mp4';
                        target.removeAttribute('hidden');
                    });
                });
            }
            window.recorder.addEventListener('start', () => {
                const info = tracks.map((track) => {
                    return {
                        capabilities: track.getCapabilities(),
                        settings: track.getSettings()
                    };
                });
                this.dispatchEvent('pwa:screen-capture:started', info);
            });
            window.recorder.addEventListener('stop', () => {
                stream.getTracks().forEach(track => track.stop());
                this.destinationTargets.forEach((target) => {
                    target.srcObject = null;
                    target.setAttribute('hidden');
                })
            });
            this.destinationTargets.forEach((target) => {
                target.removeAttribute('hidden');
                target.srcObject = new MediaStream(stream.getTracks());
            })

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
