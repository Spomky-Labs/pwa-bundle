'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['destination', 'download', 'element', 'region'];
    stream = null;

    getSupportedConstraints = async() => {
        try {
            this._checkCaptureSupported();
            const constraints = navigator.mediaDevices.getSupportedConstraints();
            this.dispatchEvent('pwa:capture:constraints', {constraints});
        } catch (error) {
            this.dispatchEvent('pwa:capture:error', {error});
        }
    }

    getSupportedDevices = async() => {
        try {
            this._checkMediaSupported();
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.dispatchEvent('pwa:capture:devices', {devices});
        } catch (error) {
            this.dispatchEvent('pwa:capture:error', {error});
        }
    }

    media = async({params}) => {
        this._hideDownload();
        try {
            this._checkMediaSupported();
            this._ensureStopped();
            this.stream = await navigator.mediaDevices.getUserMedia(params.contraints ?? {audio: true, video: true});
            this._prepareTracks();
            this._broadcast();
        } catch (error) {
            this.dispatchEvent('pwa:capture:error', {error});
        }
    }

    capture = async({params}) => {
        this._hideDownload();
        try {
            this._checkCaptureSupported();
            this._ensureStopped();
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
            this.stream = await navigator.mediaDevices.getDisplayMedia(options);
            this._prepareTracks();
            if (this.hasElementTarget) {
                this.restrictToElement({target: this.elementTarget});
            }
            if (this.hasRegionTarget) {
                this.restrictToRegion({target: this.regionTarget});
            }
            this._broadcast();
        } catch (error) {
            this.dispatchEvent('pwa:capture:error', {error});
        }
    }

    restrictToElement = async ({target}) => {
        const videoTracks = this.stream.getVideoTracks();
        if (videoTracks.length > 0) {
            const restrictionTarget = await RestrictionTarget.fromElement(target);
            await videoTracks[0].restrictTo(restrictionTarget);
        }
    }

    restrictToRegion = async ({target}) => {
        const videoTracks = this.stream.getVideoTracks();
        if (videoTracks.length > 0) {
            const cropTarget = await CropTarget.fromElement(target);
            await videoTracks[0].cropTo(cropTarget);
        }
    }

    _checkCaptureSupported = () => {
        if (!navigator.mediaDevices.getDisplayMedia) {
            throw "Your device does not support the Screen Capture API";
        }
    }

    stop = async () => {
        this._ensureStopped();
        this._hideDestination();
    }

    _checkMediaSupported = () => {
        if (!navigator.mediaDevices.getUserMedia) {
            throw "Your device does not support the User Media API";
        }
    }

    _showDestination = () => {
        this.destinationTargets.forEach((target) => {
            target.removeAttribute('hidden');
            target.srcObject = new MediaStream(this.stream.getTracks());
        })
    }

    _hideDestination = () => {
        this.destinationTargets.forEach((target) => {
            target.srcObject = null;
            target.setAttribute('hidden');
        })
    }

    _showDownload = (exportUrl) => {
        this.downloadTargets.forEach((target) => {
            target.href = exportUrl;
            target.download = 'video.mp4';
            target.removeAttribute('hidden');
        });
    }

    _hideDownload = () => {
        this.downloadTargets.forEach((target) => {
            target.setAttribute('hidden', '');
        });
    }

    _broadcast = () => {
        window.recorder = new MediaRecorder(this.stream);
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
                this.dispatchEvent('pwa:capture:available', {exportUrl});
                this._showDownload(exportUrl);
            });
        }
        window.recorder.addEventListener('start', () => {
            const info = tracks.map((track) => {
                return {
                    capabilities: track.getCapabilities(),
                    settings: track.getSettings()
                };
            });
            this.dispatchEvent('pwa:capture:started', info);
        });
        window.recorder.addEventListener('stop', () => {
            this.stream.getTracks().forEach(track => track.stop());
            this._hideDestination();
        });
        this._showDestination();

        window.recorder.start();
    }

    _prepareTracks = () => {
        const tracks = this.stream.getTracks();
        if (tracks.length === 0) {
            throw "No tracks found";
        }
        tracks.forEach((track) => {
            track.addEventListener('ended', () => {
                this.dispatchEvent('pwa:capture:track:ended', track);
                window.recorder.stop();
            });
        });
    }

    _ensureStopped = () => {
        if (this.stream === null) {
            return;
        }
        this.stream.getTracks().forEach(track => track.stop());
        this.stream = null;
    }
}
