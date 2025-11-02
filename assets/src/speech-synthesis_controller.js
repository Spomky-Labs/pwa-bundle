'use strict';
import Controller from './abstract_controller.js';

const DEFAULT_I18N = {
    loading: 'Loading voices…',
    ready: 'Ready',
    unsupported: 'SpeechSynthesis is not supported by this browser.',
    playing: 'Playing',
    paused: 'Paused',
    canceled: 'Canceled',
    finished: 'Finished'
};

/**
 * data-controller="@pwa/speech-synthesis"
 *
 * Targets:
 * - item
 * - voiceSelect
 * - status
 *
 * Values:
 * - localeValue (String)  default: "en-US"
 * - rateValue (Number)    default: 1
 * - pitchValue (Number)   default: 1
 * - volumeValue (Number)  default: 1
 * - voiceValue (String)   default: undefined
 * - enqueueValue (Boolean) default: true
 * - i18nValue (Object)    default: {}
 *
 * Events (prefix "speech"): ready, unsupported, voicesloaded, start, end, pause, resume, error, boundary, queued, dequeue, cancel, voicechange, ratechange, pitchchange, volumechange
 */
export default class extends Controller {
    static targets = ['item', 'voiceSelect', 'status'];
    static values = {
        locale: { type: String, default: 'en-US' },
        rate: { type: Number, default: 1 },
        pitch: { type: Number, default: 1 },
        volume: { type: Number, default: 1 },
        voice: String,
        enqueue: { type: Boolean, default: true },
        i18n: { type: Object, default: {} }
    };

    connect() {
        if (!('speechSynthesis' in window)) {
            this._setStatus(this._t('unsupported'));
            this.dispatchEvent('pwa:speech-synthesis:unsupported');
            return;
        }

        this._utterances = [];
        this._current = null;
        this._voices = [];
        this._voicesReady = false;

        this._bindVoicesChanged = this._onVoicesChanged.bind(this);
        window.speechSynthesis.addEventListener('voiceschanged', this._bindVoicesChanged);

        this._setStatus(this._t('loading'));
        this._loadVoices();

        this._setStatus(this._t('ready'));
        this.dispatchEvent('pwa:speech-synthesis:ready');
    }

    disconnect() {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.removeEventListener('voiceschanged', this._bindVoicesChanged);
        }
    }

    get voices() {
        return this._voices;
    }

    get locales() {
        return [...new Set(this._voices.map(v => v.lang))];
    }

    get isSpeaking() {
        return window.speechSynthesis.speaking;
    }

    speak({ params = {} }) {
        const text = params.text ?? '';
        if (!text.trim()) return;
        const utter = this._buildUtterance({ ...params, text });
        this._play(utter);
    }

    enqueueItems({ params = {} }) {
        this.itemTargets.forEach(el => {
            const text = el.dataset.speechText ?? el.textContent ?? '';
            if (!text.trim()) return;

            const itemParams = {
                text,
                sourceEl: el,
                locale: el.dataset.speechLocale ?? params.locale,
                voice: el.dataset.speechVoice ?? params.voice,
                rate: el.dataset.speechRate ? Number(el.dataset.speechRate) : params.rate,
                pitch: el.dataset.speechPitch ? Number(el.dataset.speechPitch) : params.pitch,
                volume: el.dataset.speechVolume ? Number(el.dataset.speechVolume) : params.volume
            };

            this._queue(this._buildUtterance(itemParams));
        });
        this._drainQueue();
    }

    speakItem(event) {
        const el = event.currentTarget;
        const text = el.dataset.speechText ?? el.textContent ?? '';
        if (!text.trim()) return;

        const itemParams = {
            text,
            sourceEl: el,
            locale: el.dataset.speechLocale,
            voice: el.dataset.speechVoice,
            rate: el.dataset.speechRate ? Number(el.dataset.speechRate) : undefined,
            pitch: el.dataset.speechPitch ? Number(el.dataset.speechPitch) : undefined,
            volume: el.dataset.speechVolume ? Number(el.dataset.speechVolume) : undefined
        };

        const utter = this._buildUtterance(itemParams);
        this._play(utter);
    }

    pause() {
        if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
            window.speechSynthesis.pause();
            this._setStatus(this._t('paused'));
            this.dispatchEvent('pwa:speech-synthesis:pause');
        }
    }

    resume() {
        if (window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
            this._setStatus(this._t('playing'));
            this.dispatchEvent('pwa:speech-synthesis:resume');
        }
    }

    cancel() {
        this._utterances = [];
        window.speechSynthesis.cancel();
        this._current = null;
        this._setStatus(this._t('canceled'));
        this.dispatchEvent('pwa:speech-synthesis:cancel');
    }

    changeVoiceFromSelect() {
        if (!this.hasVoiceSelectTarget) return;
        const name = this.voiceSelectTarget.value;
        this.voiceValue = name;
        this.dispatchEvent('pwa:speech-synthesis:voicechange', { name });
    }

    setRate({ params = {} }) {
        if (typeof params.rate === 'number') this.rateValue = params.rate;
        this.dispatchEvent('pwa:speech-synthesis:ratechange', { rate: this.rateValue });
    }

    setPitch({ params = {} }) {
        if (typeof params.pitch === 'number') this.pitchValue = params.pitch;
        this.dispatchEvent('pwa:speech-synthesis:pitchchange', { pitch: this.pitchValue });
    }

    setVolume({ params = {} }) {
        if (typeof params.volume === 'number') this.volumeValue = params.volume;
        this.dispatchEvent('pwa:speech-synthesis:volumechange', { volume: this.volumeValue });
    }

    _loadVoices() {
        const list = window.speechSynthesis.getVoices() || [];
        if (list.length) {
            this._voices = list.slice().sort((a, b) => (a.lang + a.name).localeCompare(b.lang + b.name));
            this._voicesReady = true;
            this._populateVoiceSelect();
            this.dispatchEvent('pwa:speech-synthesis:voicesloaded', {
                voices: this._voices.map(v => ({ name: v.name, lang: v.lang, local: v.localService }))
            });
        }
    }

    _onVoicesChanged() {
        this._loadVoices();
        if (this._voicesReady) this._setStatus(this._t('ready'));
    }

    _populateVoiceSelect() {
        if (!this.hasVoiceSelectTarget) return;
        const sel = this.voiceSelectTarget;
        sel.innerHTML = '';
        this._voices.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.name;
            opt.textContent = `${v.name} (${v.lang})${v.localService ? ' • local' : ''}`;
            sel.appendChild(opt);
        });
        if (this.voiceValue) sel.value = this.voiceValue;
    }

    _buildUtterance({ text, locale, voice, rate, pitch, volume, sourceEl } = {}) {
        const u = new SpeechSynthesisUtterance(text);

        u.lang = locale ?? this.localeValue;
        u.rate = typeof rate === 'number' ? rate : this.rateValue;
        u.pitch = typeof pitch === 'number' ? pitch : this.pitchValue;
        u.volume = typeof volume === 'number' ? volume : this.volumeValue;

        const name = voice ?? this.voiceValue;
        if (this._voicesReady) {
            if (name) {
                const v = this._voices.find(v => v.name === name) || null;
                if (v) u.voice = v;
            } else {
                const auto = this._pickVoiceFor(u.lang);
                if (auto) u.voice = auto;
            }
        }

        u.onstart = () => {
            this._current = u;
            this._setStatus(this._t('playing'));
            this.dispatchEvent('pwa:speech-synthesis:start', { text, lang: u.lang, sourceEl });
        };
        u.onend = () => {
            this._setStatus(this._t('finished'));
            this.dispatchEvent('pwa:speech-synthesis:end', { text, lang: u.lang, sourceEl });
            this._current = null;
            this._drainQueue();
        };
        u.onpause = () => this.dispatchEvent('pwa:speech-synthesis:pause', { text, sourceEl });
        u.onresume = () => this.dispatchEvent('pwa:speech-synthesis:resume', { text, sourceEl });
        u.onerror = (e) => {
            this.dispatchEvent('pwa:speech-synthesis:error', { error: e.error || e.message || 'unknown', sourceEl });
            this._current = null;
            this._drainQueue();
        };
        u.onboundary = (e) =>
            this.dispatchEvent('pwa:speech-synthesis:boundary', {
                name: e.name,
                charIndex: e.charIndex,
                charLength: e.charLength,
                elapsedTime: e.elapsedTime,
                sourceEl
            });

        return u;
    }

    _play(utter) {
        if (!this.enqueueValue) {
            this.cancel();
            window.speechSynthesis.speak(utter);
            return;
        }
        this._queue(utter);
        this._drainQueue();
    }

    _queue(utter) {
        this._utterances.push(utter);
        this.dispatchEvent('pwa:speech-synthesis:queued', { size: this._utterances.length });
    }

    _drainQueue() {
        if (!this._utterances.length) return;
        if (window.speechSynthesis.speaking || window.speechSynthesis.pending) return;
        const next = this._utterances.shift();
        window.speechSynthesis.speak(next);
        this.dispatchEvent('pwa:speech-synthesis:dequeue', { remaining: this._utterances.length });
    }

    _t(key) {
        return (this.i18nValue && this.i18nValue[key]) ?? DEFAULT_I18N[key] ?? key;
    }

    _setStatus(text) {
        if (this.hasStatusTarget) this.statusTarget.textContent = text;
    }

    _pickVoiceFor(lang) {
        if (!this._voices?.length || !lang) return null;
        return this._voices.find(v => v.lang === lang)
            ?? this._voices.find(v => v.lang?.toLowerCase().startsWith(lang.toLowerCase().split('-')[0] + '-'))
            ?? null;
    }
}
