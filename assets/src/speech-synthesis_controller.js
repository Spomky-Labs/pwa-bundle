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
 * Properties (getters):
 * - voices: Array of available voices
 * - locales: Array of available locale codes
 * - isSpeaking: Boolean - true if currently speaking
 * - isPaused: Boolean - true if paused
 * - isPending: Boolean - true if utterances are pending in native queue
 * - queueSize: Number - size of internal queue
 *
 * Methods:
 * - speak({ params }): Speak text with optional parameters
 * - speakItem(event): Speak text from clicked element
 * - enqueueItems({ params }): Enqueue multiple items
 * - pause(): Pause current speech
 * - resume(): Resume paused speech
 * - cancel(): Cancel all speech and clear queue
 * - skipToNext(): Skip to next utterance in queue
 * - clearQueue(): Clear queue without canceling current speech
 * - getVoicesByLang(lang): Get voices for specific language
 * - getVoiceByName(name): Get voice by name
 * - getDefaultVoice(): Get system default voice
 * - getCurrentUtterance(): Get current utterance object
 * - setRate({ params }): Set speech rate
 * - setPitch({ params }): Set speech pitch
 * - setVolume({ params }): Set speech volume
 * - changeVoiceFromSelect(): Update voice from select element
 *
 * Events (prefix "pwa:speech-synthesis:"):
 * - ready: Controller is ready
 * - unsupported: Browser doesn't support SpeechSynthesis
 * - voicesloaded: Voices have been loaded
 * - start: Speech started
 * - end: Speech ended
 * - pause: Speech paused
 * - resume: Speech resumed
 * - error: Speech error occurred
 * - boundary: Word/sentence boundary reached
 * - queued: Utterance added to queue
 * - dequeue: Utterance removed from queue
 * - queuechange: Queue size changed
 * - queueempty: Queue is now empty
 * - cancel: Speech canceled
 * - voicechange: Voice changed
 * - ratechange: Rate changed
 * - pitchchange: Pitch changed
 * - volumechange: Volume changed
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

    get isPaused() {
        return window.speechSynthesis.paused;
    }

    get isPending() {
        return window.speechSynthesis.pending;
    }

    get queueSize() {
        return this._utterances.length;
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

    getVoicesByLang(lang) {
        if (!lang) return [];
        const langPrefix = lang.toLowerCase().split('-')[0];
        return this._voices.filter(v =>
            v.lang === lang || v.lang?.toLowerCase().startsWith(langPrefix + '-')
        );
    }

    getVoiceByName(name) {
        if (!name) return null;
        return this._voices.find(v => v.name === name) ?? null;
    }

    getDefaultVoice() {
        return this._voices.find(v => v.default) ?? this._voices[0] ?? null;
    }

    clearQueue() {
        this._utterances = [];
        this.dispatchEvent('pwa:speech-synthesis:queuechange', { size: 0 });
        this.dispatchEvent('pwa:speech-synthesis:queueempty');
    }

    getCurrentUtterance() {
        return this._current;
    }

    skipToNext() {
        if (!this.isSpeaking) return;
        window.speechSynthesis.cancel();
        this._current = null;
        this._drainQueue();
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
        this.dispatchEvent('pwa:speech-synthesis:queuechange', { size: this._utterances.length });
    }

    _drainQueue() {
        if (!this._utterances.length) return;
        if (window.speechSynthesis.speaking || window.speechSynthesis.pending) return;
        const next = this._utterances.shift();
        window.speechSynthesis.speak(next);
        this.dispatchEvent('pwa:speech-synthesis:dequeue', { remaining: this._utterances.length });
        this.dispatchEvent('pwa:speech-synthesis:queuechange', { size: this._utterances.length });
        if (this._utterances.length === 0) {
            this.dispatchEvent('pwa:speech-synthesis:queueempty');
        }
    }

    _t(key) {
        return (this.i18nValue && this.i18nValue[key]) ?? DEFAULT_I18N[key] ?? key;
    }

    _setStatus(text) {
        if (this.hasStatusTarget) this.statusTarget.textContent = text;
    }

    _pickVoiceFor(lang) {
        if (!this._voices?.length || !lang) return null;

        const langPrefix = lang.toLowerCase().split('-')[0];

        // Try exact match with default voice first
        const exactDefault = this._voices.find(v => v.lang === lang && v.default);
        if (exactDefault) return exactDefault;

        // Try exact match with local voice
        const exactLocal = this._voices.find(v => v.lang === lang && v.localService);
        if (exactLocal) return exactLocal;

        // Try exact match
        const exact = this._voices.find(v => v.lang === lang);
        if (exact) return exact;

        // Try language prefix match with default voice
        const prefixDefault = this._voices.find(v => v.lang?.toLowerCase().startsWith(langPrefix + '-') && v.default);
        if (prefixDefault) return prefixDefault;

        // Try language prefix match with local voice
        const prefixLocal = this._voices.find(v => v.lang?.toLowerCase().startsWith(langPrefix + '-') && v.localService);
        if (prefixLocal) return prefixLocal;

        // Try language prefix match
        return this._voices.find(v => v.lang?.toLowerCase().startsWith(langPrefix + '-')) ?? null;
    }
}
