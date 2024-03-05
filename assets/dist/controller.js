import { Controller } from '@hotwired/stimulus';

var Status;
(function (Status) {
    Status["OFFLINE"] = "OFFLINE";
    Status["ONLINE"] = "ONLINE";
})(Status || (Status = {}));
class default_1 extends Controller {
    connect() {
        this.dispatchEvent('connect', {});
        if (navigator.onLine) {
            this.statusChanged({
                status: Status.ONLINE,
                message: this.onlineMessageValue,
            });
        }
        else {
            this.statusChanged({
                status: Status.OFFLINE,
                message: this.offlineMessageValue,
            });
        }
        window.addEventListener("offline", () => {
            this.statusChanged({
                status: Status.OFFLINE,
                message: this.offlineMessageValue,
            });
        });
        window.addEventListener("online", () => {
            this.statusChanged({
                status: Status.ONLINE,
                message: this.onlineMessageValue,
            });
        });
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'connection-status' });
    }
    statusChanged(data) {
        console.log('statusChanged', data);
        this.messageTargets.forEach((element) => {
            element.innerHTML = data.message;
        });
        this.attributeTargets.forEach((element) => {
            element.setAttribute('data-connection-status', data.status);
        });
        this.dispatchEvent('status-changed', { detail: data });
    }
}
default_1.targets = ['message', 'attribute'];
default_1.values = {
    onlineMessage: { type: String, default: 'You are online.' },
    offlineMessage: { type: String, default: 'You are offline.' },
};

export { default_1 as default };
