Progressive Web App for Symfony
===============================

![Build Status](https://github.com/spomky-labs/phpwa/workflows/Coding%20Standards/badge.svg)
![Build Status](https://github.com/spomky-labs/phpwa/workflows/Static%20Analyze/badge.svg)

![Build Status](https://github.com/spomky-labs/phpwa/workflows/Unit%20and%20Functional%20Tests/badge.svg)
![Build Status](https://github.com/spomky-labs/phpwa/workflows/Rector%20Checkstyle/badge.svg)

[![Latest Stable Version](https://poser.pugx.org/spomky-labs/phpwa/v/stable.png)](https://packagist.org/packages/spomky-labs/phpwa)
[![Total Downloads](https://poser.pugx.org/spomky-labs/phpwa/downloads.png)](https://packagist.org/packages/spomky-labs/phpwa)
[![Latest Unstable Version](https://poser.pugx.org/spomky-labs/phpwa/v/unstable.png)](https://packagist.org/packages/spomky-labs/phpwa)
[![License](https://poser.pugx.org/spomky-labs/phpwa/license.png)](https://packagist.org/packages/spomky-labs/phpwa)

[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/spomky-labs/phpwa/badge)](https://api.securityscorecards.dev/projects/github.com/spomky-labs/phpwa)

# Scope

This bundle provides the [spomky-labs/phpwa](https://github.com/spomky-labs/phpwa) bundle for Symfony.
This will help you to generate Progressive Web Apps (PWA) Manifests and assets (icons or screenshots).

Please have a look at the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) for more information about Progressive Web Apps.

# Installation

Install the bundle with Composer: `composer require --dev spomky-labs/phpwa`.

This project follows the [semantic versioning](http://semver.org/) strictly.

# Documentation

A Progressive Web Application (PWA) is a web application that has a manifest and can be installed on a device.
The manifest is a JSON file that describes the application and its assets (icons, screenshots, etc.).

A Service Worker can be used to provide offline capabilities to the application or to provide push notifications.

## Manifest

### Manifest Configuration

The bundle is able to generate a manifest from a configuration file.
The manifest members defined in the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) are supported
without any change, with the exception of members `icons`, `screenshots` and `shortcuts` (see below).

```yaml
# config/packages/phpwa.yaml
phpwa:
    name: 'My App'
    short_name: 'My App'
    description: 'My App description'
    start_url: '/index.html'
    display: 'standalone'
    background_color: '#ffffff'
    theme_color: '#ffffff'
    orientation: 'portrait'
    dir: 'ltr'
    lang: 'en'
    scope: '/'
    categories: ['productivity', 'utilities']
    file_handlers:
        - action: '/edit'
          accept:
            'text/*': ['.txt']
            'application/json': ['.json']
```

### Manifest Generation

When the configuration is set, you can generate the manifest with the following command:

```bash
symfony console pwa:build
```

The manifest will be generated in the `public` directory.
The manifest file name is `pwa.json` and the assets will leave in the `/public/pwa` folder by default.
You can change the output file name and the output folder with the following options:

* `--output` or `-o` to change the output file name (default: `pwa.json`)
* `--asset_folder` or `-a` to change the output folder (default: `/pwa`)
* `--public_folder` or `-p` to change the public folder (default: `%kernel.project_dir%/public`)
* `--url_prefix` or `-u` to change the URL prefix (default: ``)

```bash
symfony console pwa:build --output=manifest.json
```

The URL prefix is used to generate the relative URLs in the manifest.
For instance, if your application root URL is https://example.com/foo/bar, set the URL prefix to `/foo/bar` and the manifest will be generated with relative URLs.

```json
{
    "icons": [
        {
            "src": "/foo/bar/pwa/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/foo/bar/pwa/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ]
}
```

### Manifest Icons

The bundle is able to generate icons from a source image.
The icons must be square and the source image should be at best quality as possible.

To process the icons, you should set an icon processor. The bundle provides a GD processor and an Imagick processor.
Depending on your system, you may have to install one extension or the other.

```yaml
# config/packages/phpwa.yaml
phpwa:
    image_processor: 'pwa.image_processor.gd' # or 'pwa.image_processor.imagick'
    icons:
        - src: "%kernel.project_dir%/assets/images/logo.png"
          sizes: [48, 57, 60, 72, 76, 96, 114, 128, 144, 152, 180, 192, 256, 384, 512, 1024]
          format: 'webp'
        - src: "%kernel.project_dir%/assets/images/mask.png"
          sizes: [48, 57, 60, 72, 76, 96, 114, 128, 144, 152, 180, 192, 256, 384, 512, 1024]
          purpose: 'maskable'
        - src: "%kernel.project_dir%/assets/images/logo.svg"
          sizes: [0] # 0 means `any` size and is suitable for vector images
```

With the configuration above, the bundle will generate
* 16 icons from the `logo.png` image. The icons will be converted from `png` to `webp`.
* 16 icons from the `mask.png` image. The format will be `png` and the purpose will be `maskable`.
* And 1 icon from the `logo.svg` image. The format will be `svg` and the size will be `any`.

### Manifest Screenshots

The bundle is able to generate screenshots from a source image.
Similar to icons, the source image should be at best quality as possible.

```yaml
# config/packages/phpwa.yaml
phpwa:
    image_processor: 'pwa.image_processor.gd' # or 'pwa.image_processor.imagick'
    screenshots:
        - src: "%kernel.project_dir%/assets/screenshots/narrow.png"
          label: "View of the application home page"
        - src: "%kernel.project_dir%/assets/screenshots/wide.png"
          label: "View of the application home page"
        - src: "%kernel.project_dir%/assets/screenshots/android_dashboard.png"
          platform: 'android'
          format: 'webp'
          label: "View of the dashboard on Android"
```

The bundle will automatically generate screenshots from the source images and add additional information in the manifest
such as the `sizes` and the `form_factor` (`wide` or `narrow`).
The `format` parameter is optional. It indicates the format of the generated image. If not set, the format will be the same as the source image.

### Manifest Shortcuts

The `shortcuts` member may contain a list of icons.
The parameters are very similar to the `icons` member.

```yaml
# config/packages/phpwa.yaml
phpwa:
    image_processor: 'pwa.image_processor.gd' # or 'pwa.image_processor.imagick'
    shortcuts:
        - name: "Shortcut 1"
          short_name: "shortcut-1"
          url: "/shortcut1"
          description: "Shortcut 1 description"
          icons:
              - src: "%kernel.project_dir%/assets/images/shortcut1.png"
                sizes: [48, 72, 96, 128, 144, 192, 256, 384, 512]
                format: 'webp'
        - name: "Shortcut 2"
          short_name: "shortcut-2"
          url: "/shortcut2"
          description: "Shortcut 2 description"
          icons:
              - src: "%kernel.project_dir%/assets/images/shortcut2.png"
                sizes: [48, 72, 96, 128, 144, 192, 256, 384, 512]
                format: 'webp'
```

### Using the Manifest

The manifest can be used in your HTML pages with the following code in the `<head>` section.
In you customized the output filename or the public folder, please replace `pwa.json` with the path to your manifest file.

```html
<link rel="manifest" href="{{ asset('pwa.json') }}">
```

## Service Worker

The following command will generate a Service Worker in the `public` directory.

```bash
symfony console pwa:sw
```

You can change the output file name and the output folder with the following options:

* `--output` or `-o` to change the output file name (default: `sw.js`)
* `--public_folder` or `-p` to change the public folder (default: `%kernel.project_dir%/public`)

Next, you have to register the Service Worker in your HTML pages with the following code in the `<head>` section.
It can also be done in a JavaScript file such as `app.js`.
In you customized the output filename or the public folder, please replace `sw.js` with the path to your Service Worker file.

```html
<script>
    if (navigator.serviceWorker) {
        window.addEventListener("load", () => {
            navigator.serviceWorker.register("/sw.js", {scope: '/'});
        })
    }
</script>
```

### Service Worker Configuration

The Service Worker uses Workbox and comes with predefined configuration and recipes.
You are free to change the configuration and the recipes to fit your needs.
In particular, you can change the cache strategy, the cache expiration, the cache name, etc.

# Support

I bring solutions to your problems and answer your questions.

If you really love that project and the work I have done or if you want I prioritize your issues, then you can help me out for a couple of :beers: or more!

[Become a sponsor](https://github.com/sponsors/Spomky)

Or

[![Become a Patreon](https://c5.patreon.com/external/logo/become_a_patron_button.png)](https://www.patreon.com/FlorentMorselli)

# Contributing

Requests for new features, bug fixed and all other ideas to make this project useful are welcome.
The best contribution you could provide is by fixing the [opened issues where help is wanted](https://github.com/spomky-labs/phpwa/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22).

Please report all issues in [the main repository](https://github.com/spomky-labs/phpwa/issues).

Please make sure to [follow these best practices](.github/CONTRIBUTING.md).

# Security Issues

If you discover a security vulnerability within the project, please **don't use the bug tracker and don't publish it publicly**.
Instead, all security issues must be sent to security [at] spomky-labs.com. 

# Licence

This project is release under [MIT licence](LICENSE).
