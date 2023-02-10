# Beskedfordeler forward

Forward Beskedfordeler messages to endpoints.

## Installation

```sh
composer require itk-dev/beskedfordeler
drush pm:enable beskedfordeler_forward
```

Define endpoints to forward messages to in `settings.local.php`:

```php
$settings['beskedfordeler_forward']['endpoints'][] = …;
```

## Development

For local development and testing you can use
[localtunnel](https://localtunnel.me/) or similar tools to forward messages to
your local machine.
