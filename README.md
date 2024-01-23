Progressive Web App for Symfony
===============================

![Build Status](https://github.com/Spomky-Labs/phpwa/workflows/Coding%20Standards/badge.svg)
![Build Status](https://github.com/Spomky-Labs/phpwa/workflows/Static%20Analyze/badge.svg)

![Build Status](https://github.com/Spomky-Labs/phpwa/workflows/Unit%20and%20Functional%20Tests/badge.svg)
![Build Status](https://github.com/Spomky-Labs/phpwa/workflows/Rector%20Checkstyle/badge.svg)

[![Latest Stable Version](https://poser.pugx.org/Spomky-Labs/phpwa/v/stable.png)](https://packagist.org/packages/Spomky-Labs/phpwa)
[![Total Downloads](https://poser.pugx.org/Spomky-Labs/phpwa/downloads.png)](https://packagist.org/packages/Spomky-Labs/phpwa)
[![Latest Unstable Version](https://poser.pugx.org/Spomky-Labs/phpwa/v/unstable.png)](https://packagist.org/packages/Spomky-Labs/phpwa)
[![License](https://poser.pugx.org/Spomky-Labs/phpwa/license.png)](https://packagist.org/packages/Spomky-Labs/phpwa)

[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/Spomky-Labs/phpwa/badge)](https://api.securityscorecards.dev/projects/github.com/Spomky-Labs/phpwa)

# Scope

This bundle provides the [Spomky-Labs/phpwa](https://github.com/Spomky-Labs/phpwa) bundle for Symfony.
This will help you to generate Progressive Web Apps (PWA) Manifests and assets (icons or screenshots).
Also, it will help you to generate Service Workers based on [Workbox](https://developers.google.com/web/tools/workbox).

Please have a look at the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) for more information about Progressive Web Apps.

# Installation

Install the bundle with Composer: 

```bash
composer require Spomky-Labs/phpwa
```

This project follows the [semantic versioning](http://semver.org/) strictly.

# Documentation

A Progressive Web Application (PWA) is a web application that has a manifest and can be installed on a device.
The manifest is a JSON file that describes the application and its assets (icons, screenshots, etc.).

A Service Worker can be used to provide offline capabilities to the application or to provide push notifications.

## Manifest

### Manifest Configuration

The bundle is able to generate a manifest from a configuration file.
The manifest members defined in the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) are supported.
Other members may be added in the future.

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

On `dev` or `test` environment, the manifest and the service worker (if any) will be generated for you.
On `prod` environment, these files are compiled during the deployment with Asset Mapper.

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

Thanks to Symfony Asset Mapper, the bundle is able to link your application assets to the manifest file.
Please note that the icons of a size greater than 1024px may be ignored by the browser.

When you use images (icons or screenshots), you must define an ImageProcessor.
The bundle provides two image processors:

* `pwa.image_processor.gd`: this processor uses the GD library.
* `pwa.image_processor.imagick`: this processor uses the Imagick library.

Use one of these processors in your configuration file. You can define a custom image processor if you want.
This service must implement the `SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor` interface.

```yaml  
# config/packages/phpwa.yaml
pwa:
    image_processor: 'pwa.image_processor.gd'
    icons:
        - src: "images/logo.png"
          sizes: [48, 96, 128, 256, 512, 1024]
        - src: "images/logo.svg"
          sizes: [0] # 0 means `any` size and is suitable only for vector images
        - src: "images/logo.svg"
          purpose: 'maskable'
          sizes: 'any' # "any" is understood
        - src: "/home/foo/projectA/images/logo.png" # Absolute path
          purpose: 'maskable'
          sizes: 512 # Passing a signed integer is also understood and similar to [512]
    screenshots:
        - src: "screenshots/android_dashboard.png"
          platform: 'android'
          label: "View of the dashboard on Android"
        -  "screenshots/android_feature1.png" # Only src is required. This is a shortcut
        -  "/home/foo/projectA/images/android_feature2.png" # Absolute path
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

### URLs and Actions

The `shortcuts`, `file_handlers`, `protocol_handlers`, `related_applications` or `shared_targets`
members may contain a list of URLs/actions. You can define URLs as relative paths or by using the route name.

```yaml
# config/packages/phpwa.yaml
pwa:
    shortcuts:
        - name: "Action 0"
          short_name: "action-0"
          url: "https://example.com/action" # Absolute URL
          description: "Action 0 description"
        - name: "Action 1"
          short_name: "action-1"
          url: "/action1" # Relative path
          description: "Action 1 description"
        - name: "Action 2"
          short_name: "action-2"
          url: "app_action2" # Route name
          description: "Use this route to generate the URL"
        - name: "Action 2"
          short_name: "action-2"
          url:
            path: "app_action2" # Route name (same as above)
            params: # Route parameters
                foo: "bar"
                param1: "value1"
          description: "Use this route with parameters to generate the URL"
    file_handlers:
        - action: '/edit'
          accept:
            'text/*': ['.txt']
            'application/json': ['.json']
        - action: 'app_edit'
          accept:
            'text/*': ['.txt']
            'application/json': ['.json']
        - action:
            path: 'app_edit'
            params:
                foo: 'bar'
                param1: 'value1'
          accept:
            'text/*': ['.txt']
            'application/json': ['.json']
    protocol_handlers:
        - protocol: 'mailto'
          url: ...
    related_applications:
        - platform: 'play'
          url: ...
          id: 'com.example.app1'
        - platform: 'itunes'
          url: ...
    shared_targets:
        - url: ...
```

## Service Worker

The service worker is a JavaScript file that is executed by the browser.
It can be served by Asset Mapper.

```yaml
# config/packages/phpwa.yaml
pwa:
    serviceworker: 'sw.js' # File located in assets/sw.js
```

The bundle provides a Service Worker based on [Workbox](https://developers.google.com/web/tools/workbox).
Use the following command to enable it:

```bash
symfony console pwa:create:sw
```

This will create a Service Worker in `assets/sw.js`.
Feel free to modify it as you want.

```yaml
#The following configuration is similar
pwa:
    serviceworker:
        src: 'script/service-worker.js'
        dest: '/sw.js' # Optional
        scope: '/' # Optional
```

The Service Worker is directly injected into your HTML pages by the Twig function `pwa` (see above).
You can disable this feature by calling the Twig function with `false` as argument.

```html
{{ pwa(injectSW=false) }}
```

The injection method uses a CDN version of `workbox-window`.
You can install this package with the following command.
The injected code will detect the presence of the package and will use it if available.

```bash
symfony console importmap:require workbox-window
```

See https://developer.chrome.com/docs/workbox/using-workbox-window for more information.

### Workbox Precaching

By default, the Workbox Precaching feature is enabled.
You can disable it by removing the placeholder in the service worker file.
The placeholder is `//PRECACHING_PLACEHOLDER` and can be changed from the bundle configuration.

### Workbox Warm Cache

If you use the provided Service Worker, you can enable the Workbox Warm Cache feature.
No route is warmed by default. You have to define the routes you want to warm.
Please note that only application URLs cache be cached.

```yaml
# config/packages/phpwa.yaml
pwa:
    serviceworker:
        src: 'sw.js'
        warm_cache_urls:
            - 'app_homepage' # Simple route name
            -
                path: 'app_feature1' # Route name without parameters
            -
                path: 'app_feature2' # Route name with parameters
                params:
                    foo: 'bar'
                    param1: 'value1'
```

### Workbox Offline Fallback Page

If you use the provided Service Worker, you can enable the Workbox Offline Fallback Page feature.
By default, the offline fallback page is disabled.
You can enable it by defining the route name of the offline fallback page.

```yaml
# config/packages/phpwa.yaml
pwa:
    serviceworker:
        src: 'sw.js'
        offline_fallback: 'app_offline_page'
```

# Support

I bring solutions to your problems and answer your questions.

If you really love that project and the work I have done or if you want I prioritize your issues, then you can help me out for a couple of :beers: or more!

* [Become a sponsor](https://github.com/sponsors/Spomky)
* [Become a Patreon](https://www.patreon.com/FlorentMorselli)
* [Buy me a coffee](https://www.buymeacoffee.com/FlorentMorselli)

# Contributing

Requests for new features, bug fixed and all other ideas to make this project useful are welcome.
The best contribution you could provide is by fixing the [opened issues where help is wanted](https://github.com/Spomky-Labs/phpwa/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22).

Please report all issues in [the main repository](https://github.com/Spomky-Labs/phpwa/issues).

Please make sure to [follow these best practices](.github/CONTRIBUTING.md).

# Security Issues

If you discover a security vulnerability within the project, please **don't use the bug tracker and don't publish it publicly**.
Instead, all security issues must be sent to security [at] spomky-labs.com. 

# Licence

This project is release under [MIT licence](LICENSE).
