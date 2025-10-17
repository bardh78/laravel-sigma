# Laravel Sigma

Opinionated Laravel helper package that, in local/development, adds a "Copy Error" button to Laravel's exception page (copies the error message and file:line).

## Requirements
- **PHP**: ^8.1
- **Laravel (illuminate/support)**: ^10.0 | ^11.0 | ^12.0

See `composer.json` for full constraints.

## Installation
Install via Composer:

```bash
composer require bardh78/laravel-sigma
```

This package supports Laravel Package Auto-Discovery, so you do not need to manually register the service provider.

## Configuration
Publish the configuration file if you want to customize defaults:

```bash
php artisan vendor:publish --tag=config --provider="Bardh78\\LaravelSigma\\LaravelSigmaServiceProvider"
```

This will create `config/sigma.php` with:

```php
return [
    'enabled' => env('SIGMA_ENABLED', true),
];
```

You can control the package via environment variable:

```env
SIGMA_ENABLED=true
```

## Usage
### Local error page enhancement
When your app runs in the `local` or `development` environment, the service provider binds a custom exception renderer that adds a convenient "Copy Error" button to the exception page. The button copies the error message and the originating file path with line number (e.g., `app/Services/Foo.php:123`) to your clipboard.

No additional setup or public API is required—this is enabled automatically when the environment matches and `sigma.enabled` is true.

## Versioning
This package follows semantic versioning where possible. Refer to releases for changes.

## License
The MIT License (MIT). See the [LICENSE](LICENSE) file for details.

## Credits
- Author: Bardhyl Fejzullahu
- Package: `bardh78/laravel-sigma`
