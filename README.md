# MyAdmin HD Space VPS Addon

[![Build Status](https://github.com/detain/myadmin-hd-vps-addon/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-hd-vps-addon/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-hd-vps-addon/version)](https://packagist.org/packages/detain/myadmin-hd-vps-addon)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-hd-vps-addon/downloads)](https://packagist.org/packages/detain/myadmin-hd-vps-addon)
[![License](https://poser.pugx.org/detain/myadmin-hd-vps-addon/license)](https://packagist.org/packages/detain/myadmin-hd-vps-addon)

An addon plugin for the MyAdmin VPS module that enables the purchase and management of additional hard drive space for virtual private servers. This plugin integrates with the MyAdmin service platform via Symfony EventDispatcher hooks to provide HD space upselling, provisioning, and billing capabilities.

## Features

- Purchase additional HD space (1-100 GB) for VPS instances
- Automatic billing integration with prorated pricing
- Enable/disable HD space addons with queue-based provisioning
- Admin settings for configuring per-GB cost
- CSRF-protected purchase flow with slider-based UI

## Installation

Install via Composer:

```sh
composer require detain/myadmin-hd-vps-addon
```

## Usage

The plugin registers itself through the MyAdmin plugin system using Symfony EventDispatcher hooks:

- `function.requirements` - Registers the VPS HD space page requirement
- `vps.load_addons` - Registers the HD space addon handler
- `vps.settings` - Adds HD space cost configuration to admin settings

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](LICENSE) license.
