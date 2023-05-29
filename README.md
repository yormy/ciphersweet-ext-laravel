# Anonymizer Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yormy/ciphersweet-ext-laravel.svg?style=flat-square)](https://packagist.org/packages/yormy/ciphersweet-ext-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/yormy/ciphersweet-ext-laravel.svg?style=flat-square)](https://packagist.org/packages/yormy/ciphersweet-ext-laravel)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/facade/ignition/run-php-tests?label=Tests)
![Alt text](./coverage.svg)

# Goal
This package extends the laravel-ciphersweet package with a few cool helpers

# Installation
```
composer require yormy/ciphersweet-ext-laravel

php artisan vendor:publish --tag="ciphersweet-migrations"
php artisan migrate
```

# Publishing config
```
php artisan vendor:publish --tag="ciphersweet-config"
php artisan vendor:publish --provider="Yormy\CiphersweetExtLaravel\AnonymizerServiceProvider"
```

# Spatie instructions
https://github.com/spatie/laravel-ciphersweet

# 


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Yormy](https://gitlab.com/yormy)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
