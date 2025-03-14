'use strict';

import AbstractController from './abstract_controller.js';

export default class extends AbstractController {
    ongoingTouches = [];

    connect() {
        this.element.addEventListener("touchstart", this.onTouchStart);
        this.element.addEventListener("touchmove", this.onTouchMove);
        this.element.addEventListener("touchcancel", this.onTouchCancel);
        this.element.addEventListener("touchend", this.onTouchEnd);
    }

    copyTouch = ({ identifier, clientX, clientY, pageX, pageY, radiusX, radiusY, screenX, screenY, force, rotationAngle})=> {
        return {
            identifier, clientX, clientY, pageX, pageY, radiusX, radiusY, screenX, screenY, force, rotationAngle,
            top: this.element.offsetTop,
            left: this.element.offsetLeft,
        };
    }

    onTouchEnd = (event) => {
        event.preventDefault();
        const {changedTouches} = event;
        for (let i = 0; i < changedTouches.length; i++) {
            const idx = this.ongoingTouchIndexById(changedTouches[i].identifier);
            this.ongoingTouches.splice(idx, 1);
            this.dispatchEvent('pwa:touch:ended', { touch: changedTouches[i], bubbles: true });
        }
        this.dispatchEvent('pwa:touch:updated', { touches: this.ongoingTouches, bubbles: true });
    }

    onTouchCancel = (event) => {
        event.preventDefault();
        const {changedTouches} = event;
        for (let i = 0; i < changedTouches.length; i++) {
            const idx = this.ongoingTouchIndexById(changedTouches[i].identifier);
            this.ongoingTouches.splice(idx, 1);
            this.dispatchEvent('pwa:touch:cancelled', { touch: changedTouches[i], bubbles: true });
        }
        this.dispatchEvent('pwa:touch:updated', { touches: this.ongoingTouches, bubbles: true });
    }

    onTouchMove = (event) => {
        event.preventDefault();
        const {changedTouches} = event;
        for (let i = 0; i < changedTouches.length; i++) {
            const idx = this.ongoingTouchIndexById(changedTouches[i].identifier);
            this.ongoingTouches.splice(idx, 1, this.copyTouch(changedTouches[i]))
            this.dispatchEvent('pwa:touch:moved', { touch: changedTouches[i], bubbles: true });
        }
        this.dispatchEvent('pwa:touch:updated', { touches: this.ongoingTouches, bubbles: true });
    }

    onTouchStart = (event) => {
        event.preventDefault();
        const {changedTouches} = event;
        for (let i = 0; i < changedTouches.length; i++) {
            this.ongoingTouches.push(this.copyTouch(changedTouches[i]));
            this.dispatchEvent('pwa:touch:started', { touch: changedTouches[i], bubbles: true });
        }
        this.dispatchEvent('pwa:touch:updated', { touches: this.ongoingTouches, bubbles: true });
    }

    ongoingTouchIndexById = (idToFind) => {
        for (let i = 0; i < this.ongoingTouches.length; i++) {
            const id = this.ongoingTouches[i].identifier;

            if (id === idToFind) {
                return i;
            }
        }
        return -1;
    }
}
