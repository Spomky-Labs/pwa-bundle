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
Also, it will help you to generate Service Workers based on [Workbox](https://developers.google.com/web/tools/workbox).

Please have a look at the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) for more information about Progressive Web Apps.

# Installation

Install the bundle with Composer: 

```bash
composer require spomky-labs/phpwa
```

This project follows the [semantic versioning](http://semver.org/) strictly.

# Documentation

A Progressive Web Application (PWA) is a web application that has a manifest and can be installed on a device.
The manifest is a JSON file that describes the application and its assets (icons, screenshots, etc.).

A Service Worker can be used to provide offline capabilities to the application or to provide push notifications.

## Manifest

### Manifest Configuration

The bundle is able to generate a manifest from a configuration file.
The manifest members defined in the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) are supported
without any change, with the exception of some members described in the following sections.

```yaml
# config/packages/phpwa.yaml
pwa:
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

### Using the Manifest

The manifest can be used in your HTML pages with the following Twig function in the `<head>` section.
It will automatically add the manifest your HTML pages and any other useful meta tags.

```html
{{ pwa() }}
```

### Manifest Generation

On `dev` or `test` environment, the manifest will be generated for you.
On `prod` environment, the manifest is compiled during the deployment with Asset Mapper.

```bash
symfony console asset-map:compile
```

By default, the manifest will be generated in the `public` directory with the name `/site.webmanifest`.
You can change the output file name and the output folder with the following configuration option:

```yaml
# config/packages/phpwa.yaml
pwa:
    manifest_public_url: '/foo/pwa.json'
```

### Manifest Icons and Screenshots

The bundle is able to link your assets to the manifest file.
Please note that the icons of a size greater than 1024px may be ignored by the browser.

```yaml
# config/packages/phpwa.yaml
pwa:
    image_processor: 'pwa.image_processor.gd' # or 'pwa.image_processor.imagick'
    icons:
        - src: "images/logo.png"
          sizes: [48, 96, 128, 256, 512, 1024]
        - src: "images/logo.svg"
          sizes: [0] # 0 means `any` size and is suitable for vector images
        - src: "images/logo.svg"
          purpose: 'maskable'
          sizes: [0]
    screenshots:
        - src: "screenshots/android_dashboard.png"
          platform: 'android'
          label: "View of the dashboard on Android"
        -  "screenshots/android_feature1.png"
        -  "screenshots/android_feature2.png"
    shortcuts:
        - name: "Shortcut 1"
          short_name: "shortcut-1"
          url: "/shortcut1"
          description: "Shortcut 1 description"
          icons:
              - src: "images/shortcut1.png"
                sizes: [48, 96, 128, 256, 512, 1024]
        - name: "Shortcut 2"
          short_name: "shortcut-2"
          url: "/shortcut2"
          description: "Shortcut 2 description"
          icons:
              - src: "images/shortcut2.png"
                sizes: [48, 96, 128, 256, 512, 1024]
```

### Manifest Shortcuts

The `shortcuts` member may contain a list of action shortcuts that point to specific URLs in your application.
You can define URLs as relative paths or by using the route name.

```yaml
# config/packages/phpwa.yaml
pwa:
    shortcuts:
        - name: "Action 1"
          short_name: "action-1"
          url: "/action1"
          description: "Action 1 description"
        - name: "Action 2"
          short_name: "action-2"
          url: "app_action2"
          description: "Use this route to generate the URL"
```

## Service Worker

The service worker is a JavaScript file that is executed by the browser.
It can be served by Asset Mapper.

```yaml
# config/packages/phpwa.yaml
pwa:
    serviceworker: 'script/service-worker.js'

```

```yaml
#The following configuration is similar
pwa:
    serviceworker:
        src: 'script/service-worker.js'
        dest: '/sw.js'
        scope: '/'
```

Next, you have to register the Service Worker in your HTML pages with the following code in the `<head>` section.
It can also be done in a JavaScript file such as `app.js`.
In you customized the destination filename, please replace `/sw.js` with the path to your Service Worker file.

```html
<script>
    if (navigator.serviceWorker) {
        window.addEventListener("load", () => {
            navigator.serviceWorker.register("/sw.js", {scope: '/'});
        })
    }
</script>
```

The `serviceworker.scope` member may be set to the same location or to a sub-folder.
Do not forget to update the `scope` member in the JS configuration.

### Service Worker Configuration

The Service Worker uses Workbox and comes with predefined configuration and recipes.
You are free to change the configuration and the recipes to fit your needs.
In particular, you can change the cache strategy, the cache expiration, the cache name, etc.
Please refer to the [Workbox documentation](https://developers.google.com/web/tools/workbox).

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
