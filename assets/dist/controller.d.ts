import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets: string[];
    static values: {
        onlineMessage: {
            type: StringConstructor;
            default: string;
        };
        offlineMessage: {
            type: StringConstructor;
            default: string;
        };
    };
    readonly onlineMessageValue: string;
    readonly offlineMessageValue: string;
    readonly attributeTargets: HTMLElement[];
    readonly messageTargets: HTMLElement[];
    connect(): void;
    dispatchEvent(name: any, payload: any): void;
    statusChanged(data: any): void;
}
