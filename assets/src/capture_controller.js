'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['element', 'region'];
    stream = null;
    recorder = null;

    disconnect() {
        this.stop();
    }

    record(timeSlice = 1000) {
        if (this.recorder && this.recorder.state === 'inactive') {
            this.recorder.start(timeSlice);
        }
    }

    isRecording() {
        return this.recorder?.state === 'recording';
    }

    getSupportedConstraints = async() => {
        try {
            this._checkCaptureSupported();
            const constraints = navigator.mediaDevices.getSupportedConstraints();
            this.dispatchEvent('constraints', {constraints});
        } catch (error) {
            this.dispatchEvent('error', {error});
        }
    }

    getSupportedDevices = async() => {
        try {
            this._checkMediaSupported();
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.dispatchEvent('devices', {devices});
        } catch (error) {
            this.dispatchEvent('error', {error});
        }
    }

    media = async({params}) => {
        try {
            this.stop();
            this._checkMediaSupported();
            this.stream = await navigator.mediaDevices.getUserMedia(params.constraints ?? {audio: true, video: true});
            this._prepareTracks();
            this._broadcast();
        } catch (error) {
            this.dispatchEvent('error', {error});
        }
    }

    capture = async({params}) => {
        try {
            this.stop();
            this._checkCaptureSupported();
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
                await this.restrictToElement({target: this.elementTarget});
            }
            if (this.hasRegionTarget) {
                await this.restrictToRegion({target: this.regionTarget});
            }
            this._broadcast();
        } catch (error) {
            this.dispatchEvent('error', {error});
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
            throw new Error("Your device does not support the Screen Capture API");
        }
    }

    stop = () => {
        if (this.recorder && this.recorder.state !== 'inactive') {
            this.recorder.stop(); // Ne pas nettoyer ici, attendre l'événement
            return;
        }

        this._cleanUp();
    }

    _cleanUp = () => {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        this.recorder = null;
        this.dispatchEvent('recorder:stop');
    }

    _checkMediaSupported = () => {
        if (!navigator.mediaDevices.getUserMedia) {
            throw new Error("Your device does not support the User Media API");
        }
    }

    _broadcast = () => {
        this.recorder = new MediaRecorder(this.stream);
        this.recorder.addEventListener('dataavailable', (event) => {
            this.dispatchEvent('recorder:data', {data: event.data});
        });
        this.recorder.addEventListener('start', () => {
            const info = this.stream.getTracks().map((track) => {
                return {
                    capabilities: track.getCapabilities(),
                    settings: track.getSettings()
                };
            });
            this.dispatchEvent('recorder:start', {stream: this.stream});
        });
        this.recorder.addEventListener('stop', () => this._cleanUp());

        this.recorder.start();
    }

    _prepareTracks = () => {
        const tracks = this.stream.getTracks();
        if (tracks.length === 0) {
            throw new Error("No tracks found");
        }
        tracks.forEach((track) => {
            track.addEventListener('ended', () => {
                this.dispatchEvent('track:ended', track);
                this.recorder.stop();
            });
        });
    }
}
