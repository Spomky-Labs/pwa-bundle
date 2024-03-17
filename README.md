# Progressive Web App for Symfony

![Build Status](https://github.com/Spomky-Labs/pwa-bundle/workflows/Coding%20Standards/badge.svg)
![Build Status](https://github.com/Spomky-Labs/pwa-bundle/workflows/Static%20Analyze/badge.svg)

![Build Status](https://github.com/Spomky-Labs/pwa-bundle/workflows/Unit%20and%20Functional%20Tests/badge.svg)
![Build Status](https://github.com/Spomky-Labs/pwa-bundle/workflows/Rector%20Checkstyle/badge.svg)

[![Latest Stable Version](https://poser.pugx.org/Spomky-Labs/pwa-bundle/v/stable.png)](https://packagist.org/packages/Spomky-Labs/pwa-bundle)
[![Total Downloads](https://poser.pugx.org/Spomky-Labs/pwa-bundle/downloads.png)](https://packagist.org/packages/Spomky-Labs/pwa-bundle)
[![Latest Unstable Version](https://poser.pugx.org/Spomky-Labs/pwa-bundle/v/unstable.png)](https://packagist.org/packages/Spomky-Labs/pwa-bundle)
[![License](https://poser.pugx.org/Spomky-Labs/pwa-bundle/license.png)](https://packagist.org/packages/Spomky-Labs/pwa-bundle)

[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/Spomky-Labs/pwa-bundle/badge)](https://api.securityscorecards.dev/projects/github.com/Spomky-Labs/pwa-bundle)

# Scope

This bundle provides the [Spomky-Labs/pwa-bundle](https://github.com/Spomky-Labs/pwa-bundle) bundle for Symfony.
This will help you to generate Progressive Web Apps (PWA) Manifests and assets (icons or screenshots).
Also, it will help you to generate Service Workers based on [Workbox](https://developers.google.com/web/tools/workbox).

Please have a look at the [Web app manifests](https://developer.mozilla.org/en-US/docs/Web/Manifest) for more information about Progressive Web Apps.

# Installation

Install the bundle with Composer:

```bash
composer require spomky-labs/pwa-bundle
```

If you want to use the commands to generate icons and screenshots, install the necessary dependencies:

```bash
composer require symfony/panther dbrekelmans/bdi symfony/mime symfony/filesystem --dev
vendor/bin/bdi detect drivers
bin/console pwa:create:icons --help
```

This project follows the [semantic versioning](http://semver.org/) strictly.

# Documentation

The documentation is available at https://pwa.spomky-labs.com/

# Support

I bring solutions to your problems and answer your questions.

If you really love that project and the work I have done or if you want I prioritize your issues, then you can help me out for a couple of :beers: or more!

-   [Become a sponsor](https://github.com/sponsors/Spomky)
-   [Become a Patreon](https://www.patreon.com/FlorentMorselli)
-   [Buy me a coffee](https://www.buymeacoffee.com/FlorentMorselli)

# Contributing

Requests for new features, bug fixed and all other ideas to make this project useful are welcome.
The best contribution you could provide is by fixing the [opened issues where help is wanted](https://github.com/Spomky-Labs/pwa-bundle/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22).

Please report all issues in [the main repository](https://github.com/Spomky-Labs/pwa-bundle/issues).

Please make sure to [follow these best practices](.github/CONTRIBUTING.md).

# Security Issues

If you discover a security vulnerability within the project, please **don't use the bug tracker and don't publish it publicly**.
Instead, all security issues must be sent to security [at] spomky-labs.com.

# Licence

This project is release under [MIT licence](LICENSE).
