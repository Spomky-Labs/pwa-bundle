CBOR Encder/Decoder for Symfony
===============================

![Build Status](https://github.com/spomky-labs/cbor-bundle/workflows/Coding%20Standards/badge.svg)
![Build Status](https://github.com/spomky-labs/cbor-bundle/workflows/Static%20Analyze/badge.svg)

![Build Status](https://github.com/spomky-labs/cbor-bundle/workflows/Unit%20and%20Functional%20Tests/badge.svg)
![Build Status](https://github.com/spomky-labs/cbor-bundle/workflows/Rector%20Checkstyle/badge.svg)

[![Latest Stable Version](https://poser.pugx.org/spomky-labs/cbor-bundle/v/stable.png)](https://packagist.org/packages/spomky-labs/cbor-bundle)
[![Total Downloads](https://poser.pugx.org/spomky-labs/cbor-bundle/downloads.png)](https://packagist.org/packages/spomky-labs/cbor-bundle)
[![Latest Unstable Version](https://poser.pugx.org/spomky-labs/cbor-bundle/v/unstable.png)](https://packagist.org/packages/spomky-labs/cbor-bundle)
[![License](https://poser.pugx.org/spomky-labs/cbor-bundle/license.png)](https://packagist.org/packages/spomky-labs/cbor-bundle)

# Scope

This bundle wraps the [spomky-labs/cbor-php](https://github.com/spomky-labs/cbor-bundle) library and provides the decoder as a service
This will help you to easily decode CBOR streams (Concise Binary Object Representation from [RFC8949](https://datatracker.ietf.org/doc/html/rfc8949)).

# Installation

Install the bundle with Composer: `composer require spomky-labs/cbor-bundle`.

This project follows the [semantic versioning](http://semver.org/) strictly.

# Documentation

## Object Creation

For object creation, please refer to [the documentation of the library](https://github.com/Spomky-Labs/cbor-php#object-creation).

## Object Loading

If you want to load a CBOR encoded data, you just have to use de decoder available from the container.

```php
<?php

use SpomkyLabs\CborBundle\CBORDecoder;

// CBOR object (shall be a binary string; in hex for the example)
$data = hex2bin('fb3fd5555555555555');

// Load the data
$object = $container->get(CBORDecoder::class)->decode($data); // Return a CBOR\OtherObject\DoublePrecisionFloatObject class with normalized value ~0.3333 (=1/3)
```

## Custom Tags / Other Objects

*To be written*

# Support

I bring solutions to your problems and answer your questions.

If you really love that project and the work I have done or if you want I prioritize your issues, then you can help me out for a couple of :beers: or more!

[Become a sponsor](https://github.com/sponsors/Spomky)

Or

[![Become a Patreon](https://c5.patreon.com/external/logo/become_a_patron_button.png)](https://www.patreon.com/FlorentMorselli)

# Contributing

Requests for new features, bug fixed and all other ideas to make this project useful are welcome.
The best contribution you could provide is by fixing the [opened issues where help is wanted](https://github.com/spomky-labs/cbor-bundle/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22).

Please report all issues in [the main repository](https://github.com/spomky-labs/cbor-bundle/issues).

Please make sure to [follow these best practices](.github/CONTRIBUTING.md).

# Security Issues

If you discover a security vulnerability within the project, please **don't use the bug tracker and don't publish it publicly**.
Instead, all security issues must be sent to security [at] spomky-labs.com. 

# Licence

This project is release under [MIT licence](LICENSE).
