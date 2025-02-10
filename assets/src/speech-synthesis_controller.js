'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.populateVoiceList();
  }

  locales = () => {
    return speechSynthesis.getVoices().map((voice) => voice.lang);
  }

  voices = ({params}) => {
    const voices = speechSynthesis.getVoices()
      .filter((voice) => params.locale ? voice.lang === params.locale : true)
      .filter((voice) => params.type === 'distant' ? voice.localService === false : true)
      .filter((voice) => params.type === 'local' ? voice.localService === true : true)
    ;
    console.log(voices);

    return voices;
  }

  speak = ({params}) => {
    const utterance = new SpeechSynthesisUtterance(params.text);
    utterance.voice = speechSynthesis.getVoices().find((voice) => voice.name === params.voice);
    utterance.lang = params.locale || 'en-US';
    utterance.rate = params.rate || 1;
    utterance.pitch = params.pitch || 1;
    speechSynthesis.speak(utterance);
  }

  populateVoiceList = () => {
    if (typeof speechSynthesis === "undefined") {
      return;
    }
    speechSynthesis.getVoices();
  }
}
