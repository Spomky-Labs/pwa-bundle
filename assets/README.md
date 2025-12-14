# @spomky-labs/pwa-bundle

Web APIs Stimulus controllers for your Progressive Web Applications.

This package provides ready-to-use [Stimulus](https://stimulus.hotwired.dev/) controllers that leverage modern Web APIs to enhance your Progressive Web Applications with native-like features.

## Features

- **Background Sync**: Queue and sync data in the background
- **Service Worker**: Manage service worker lifecycle and updates
- **Web Push**: Handle push notifications
- **Install Prompt**: Trigger PWA installation
- **Offline Detection**: Monitor network connectivity
- **Geolocation**: Access device location
- **Battery Status**: Monitor battery level and charging status
- **Device Sensors**: Access motion and orientation data
- **Media Capture**: Handle camera and microphone access
- **Web Share**: Share content across apps
- **Wake Lock**: Prevent screen from sleeping
- **Vibration**: Trigger device haptic feedback
- **Fullscreen**: Toggle fullscreen mode
- **Picture-in-Picture**: Enable floating video windows
- **And many more!**

## Installation

```bash
npm install @spomky-labs/pwa-bundle
```

### Dependencies

This package requires:

- `@hotwired/stimulus` ^3.0.0
- `idb` ^8.0
- `idb-keyval` ^6.2

These are listed as peer dependencies and should be installed in your project.

### Usage with Module Bundlers

If you're using a module bundler (webpack, Vite, etc.) without Symfony UX, you can import controllers directly:

```javascript
// Import all controllers
import {
    ServiceWorkerController,
    InstallController,
    WebPushController,
    GeolocationController
} from '@spomky-labs/pwa-bundle';

// Register with Stimulus
import { Application } from '@hotwired/stimulus';
const app = Application.start();
app.register('service-worker', ServiceWorkerController);
app.register('install', InstallController);
app.register('web-push', WebPushController);
app.register('geolocation', GeolocationController);
```

## Available Controllers

| Controller | Description | Main API Used |
|------------|-------------|---------------|
| **BackgroundFetchController** | Handle large file downloads in the background | Background Fetch API |
| **BackgroundSyncFormController** | Sync form submissions when back online | Background Sync API |
| **BackgroundQueueController** | Queue requests for background sync | Background Sync API |
| **BadgeController** | Display notification badges on app icon | Badging API |
| **BarcodeDetectionController** | Detect and read barcodes | Barcode Detection API |
| **BatteryController** | Monitor battery status | Battery Status API |
| **CaptureController** | Access camera and microphone | Media Capture API |
| **ConnectionStatusController** | Monitor online/offline status | Network Information API |
| **ContactController** | Access device contacts | Contact Picker API |
| **DeviceMotionController** | Track device motion events | Device Motion API |
| **DeviceOrientationController** | Track device orientation | Device Orientation API |
| **FileHandlingController** | Register app as file handler | File Handling API |
| **FullscreenController** | Toggle fullscreen mode | Fullscreen API |
| **GeolocationController** | Get device location | Geolocation API |
| **InstallController** | Trigger PWA installation prompt | BeforeInstallPrompt API |
| **NetworkInformationController** | Get network connection info | Network Information API |
| **PictureInPictureController** | Enable picture-in-picture video | Picture-in-Picture API |
| **PrefetchOnDemandController** | Prefetch resources on demand | Cache API |
| **PresentationController** | Present to external displays | Presentation API |
| **ReceiverController** | Receive presentation content | Presentation API |
| **ServiceWorkerController** | Manage service worker lifecycle | Service Worker API |
| **SpeechSynthesisController** | Text-to-speech synthesis | Web Speech API |
| **TouchController** | Handle touch events | Touch Events API |
| **VibrationController** | Trigger device vibration | Vibration API |
| **WakeLockController** | Prevent screen from sleeping | Screen Wake Lock API |
| **WebPushController** | Handle push notifications | Push API |
| **WebShareController** | Share content across apps | Web Share API |


## Symfony Integration

This package is designed to work seamlessly with the [spomky-labs/pwa-bundle](https://github.com/spomky-labs/pwa-bundle) Symfony bundle:

```bash
composer require spomky-labs/pwa-bundle
```

When installed via Composer with Symfony Flex, the controllers are automatically registered and available in your Twig templates:

```twig
<form {{ stimulus_controller('@spomky-labs/pwa/...') }}>
    {# ... #}
</form>
```

For detailed Symfony integration documentation, visit:

- [Bundle documentation](https://pwa.spomky-labs.com)
- [Main framework repository](https://github.com/spomky-labs/pwa-bundle)

## Example Usage

### Service Worker Registration

```javascript
import { Application } from '@hotwired/stimulus';
import { ServiceWorkerController } from '@spomky-labs/pwa-bundle';

const app = Application.start();
app.register('service-worker', ServiceWorkerController);
```

```html
<div data-controller="service-worker"
     data-service-worker-url-value="/sw.js">
    <!-- Service worker will auto-register -->
</div>
```

### Install Prompt

```javascript
import { InstallController } from '@spomky-labs/pwa-bundle';
app.register('install', InstallController);
```

```html
<button data-controller="install"
        data-action="click->install#prompt">
    Install App
</button>
```

### Geolocation

```javascript
import { GeolocationController } from '@spomky-labs/pwa-bundle';
app.register('geolocation', GeolocationController);
```

```html
<div data-controller="geolocation"
     data-action="geolocation:position->geolocation#onPosition">
    <button data-action="click->geolocation#getCurrentPosition">
        Get Location
    </button>
</div>
```

## Browser Support

These controllers use modern Web APIs that may not be available in all browsers. Always check for feature support before using:

- Service Worker API: Chrome 40+, Firefox 44+, Safari 11.1+
- Push API: Chrome 50+, Firefox 44+, Safari 16+
- Background Sync: Chrome 49+, Edge 79+
- Geolocation: All modern browsers
- Web Share: Chrome 89+, Safari 12.1+, Firefox (Android only)

For detailed browser compatibility, refer to [Can I Use](https://caniuse.com/) for each specific Web API.


## Development

This package is part of the [Spomky-Labs PWA Bundle](https://github.com/Spomky-Labs/pwa-bundle) monorepo.

For development:

```bash
# Clone the main repository
git clone https://github.com/Spomky-Labs/pwa-bundle.git

# Install dependencies
cd pwa-bundle
composer install

# Work on assets
cd assets
npm install
```

## Resources

- **Documentation**: https://pwa.spomky-labs.com
- **Stimulus Framework**: https://stimulus.hotwired.dev/
- **MDN Web APIs**: https://developer.mozilla.org/en-US/docs/Web/API


## License

MIT - See [LICENSE](LICENSE) file for details.

## Contributing

Please submit issues and pull requests to the repository:

- **Issues**: https://github.com/spomky-labs/pwa-bundle/issues
- **Pull Requests**: https://github.com/spomky-labs/pwa-bundle/pulls

## Credits

- **Author**: [Florent Morselli](https://github.com/Spomky)
- **Contributors**: [All contributors](https://github.com/spomky-labs/pwa-bundle/graphs/contributors)
