'use strict';

import AbstractController from './abstract_controller.js';

/* stimulusFetch: 'lazy' */
export default class extends AbstractController {
    static targets = ['container', 'floating'];
    floatingElement = null;
    previousElement = null;

    connect = () => {
        if (!"documentPictureInPicture" in window) {
            this.dispatchEvent('pwa:pip:unsupported')
            return;
        }
        this.dispatchEvent('pwa:pip:supported');
        window.documentPictureInPicture.addEventListener("enter", (event) => {
            this.dispatchEvent('pwa:pip:enter', {pipWindow: event.window});
        });
        this.floatingElement = this.floatingTarget ?? this.containerTarget.firstElementChild;
        this.previousElement = this.floatingElement.previousElementSibling;
        console.log(this.previousElement);
    }

    toggle = async ({params}) => {
        if (window.documentPictureInPicture.window) {
            window.documentPictureInPicture.window.close();
            return;
        }

        const pipWindow = await window.documentPictureInPicture.requestWindow({
            width: this.floatingElement.clientWidth,
            height: this.floatingElement.clientHeight,
        });

        pipWindow.addEventListener("pagehide", () => {
            this.restoreFloatingElement();
            this.dispatchEvent('pwa:pip:exit');
        });

        if (params.propagateStyle ?? true) {
            [...document.styleSheets].forEach((styleSheet) => {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.type = styleSheet.type;
                link.media = styleSheet.media;
                link.href = styleSheet.href;
                if (styleSheet.nonce) {
                    link.nonce = styleSheet.nonce;
                }
                pipWindow.document.head.appendChild(link);
            });
        }

        pipWindow.document.body.append(this.floatingElement);
    }

    restoreFloatingElement = () => {
        if (this.previousElement === null) {
            console.log("On place this.floatingElement au début du conteneur");
            this.containerTarget.prepend(this.floatingElement);
        } else {
            console.log("On place this.floatingElement juste après this.previousElement");
            this.previousElement.after(this.floatingElement);
        }
    }
}
