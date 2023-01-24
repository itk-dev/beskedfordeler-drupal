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
