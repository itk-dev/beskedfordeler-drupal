# Beskedfordeler

Beskedfordeler for Drupal.

<https://digitaliseringskataloget.dk/kom-godt-i-gang-vejledninger>
» “Kom godt i gang med”
» “Fælleskommunal Beskedfordeler”
(<https://docs.kombit.dk/latest/ba48e791>)

<https://digitaliseringskataloget.dk/kom-godt-i-gang-vejledninger>
» “Kom godt i gang med”
» “Certifikater”
(<https://docs.kombit.dk/latest/81fa3a9e>)

## Installation

```sh
composer require itk-dev/beskedfordeler-drupal
drush pm:enable beskedfordeler
```

Installing the module enables an `beskedfordeler/PostStatusBeskedModtag`
endpoint to which the Beskedfordeler can send messages. See “[Fælleskommunal
Beskedfordeler](https://docs.kombit.dk/latest/ba48e791)” for some details on how
to use the endpoint.

## Client certificates

Download root certificates from
<https://digitaliseringskataloget.dk/teknik/certifikater>.

For testing purposes self-signed certificates kan be used. See
<https://dev.to/darshitpp/how-to-implement-two-way-ssl-with-nginx-2g39#creating-certificates>
for details and make a request along the lines of

```sh
curl --location --data '<e/>' --header 'content-type: application/xml' …/beskedfordeler/PostStatusBeskedModtag --cert user.pfx --cert-type P12
```

## nginx setup

Beskedfordeler requires ["Mutual
TLS"](https://www.google.com/search?q=Mutual+TLS) (cf.
<https://docs.kombit.dk/latest/81fa3a9e>) and we need some special `nginx`
tricks to make this work on just the Beskedfordeler routes (cf.
<https://serverfault.com/a/1068211>).

```nginx
server {
  …

  # Enables mutual TLS/two way SSL to verify the client
  # We use `optional` (rather than `on`) to be able to require this only on the Beskedfordeler routes (cf. <https://serverfault.com/a/1068211>).
  ssl_verify_client optional;
  ssl_client_certificate …/trusted_ca.pem;

  # We may be redirected to to a path with a language prefix and therefore we check if end of location match the Beskedfordeler route.
  location ~ /beskedfordeler/PostStatusBeskedModtag$ {
    @see https://serverfault.com/a/1068211
    if ($ssl_client_verify != "SUCCESS") { return 403; }

    # Pass the request on to Drupal
    rewrite ^/(.*)$ /index.php?q=$1 last;
  }

  …

  location ~ '\.php$|^/update.php' {
    …

    # Include ssl info for debugging (cf. https://serverfault.com/a/1068211)
    fastcgi_param SSL_CLIENT_VERIFY $ssl_client_verify;
    fastcgi_param SSL_CLIENT_S_DN $ssl_client_s_dn;
    fastcgi_param SSL_CLIENT_I_DN $ssl_client_i_dn;
    fastcgi_param SSL_PROTOCOL $ssl_protocol;
    fastcgi_param SSL_CLIENT_SERIAL $ssl_client_serial;
    fastcgi_param SSL_CLIENT_V_END $ssl_client_v_end;
    fastcgi_param SSL_CLIENT_V_REMAIN $ssl_client_v_remain;
    fastcgi_param SSL_CLIENT_FINGERPRINT $ssl_client_fingerprint;

    …
  }
}
```

## Event subscribers

An event subscriber must be created to do something useful when getting a
message from Beskedfordeler:

```php
<?php
# my_module/src/EventSubscriber/BeskedfordelerEventSubscriber.php;
namespace Drupal\my_module\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BeskedfordelerEventSubscriber implements EventSubscriberInterface {
  public static function getSubscribedEvents() {
    return [
      PostStatusBeskedModtagEvent::class => 'postStatusBeskedModtag',
    ];
  }

  public function postStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    // Do something with the event.
  }

}
```

```yaml
# my_module/my_module.services.yml
services:
  Drupal\my_module\EventSubscriber\BeskedfordelerEventSubscriber:
    tags:
      - { name: 'event_subscriber' }
```

## Forwarding messages

The [Beskedfordeler forward module](modules/beskedfordeler_forward/README.md)
can be used to forward messages received on the
`beskedfordeler/PostStatusBeskedModtag` endpoint. See
<modules/beskedfordeler_forward/README.md> for details on the module.

## Storing messages in database

The [Beskedfordeler database](modules/beskedfordeler_database/README.md) can be
used to store messages received on the `beskedfordeler/PostStatusBeskedModtag`
endpoint in the database. See <modules/beskedfordeler_database/README.md> for
details on the module.

## Test

```sh
curl --data '<e/>' --header 'content-type: application/xml' …/beskedfordeler/PostStatusBeskedModtag
```

## Coding standards

All coding standards are checked with [GitHub
Actions](https://github.com/features/actions) when a pull request is made (cf.
[pr.yaml](.github/workflows/pr.yaml)).

Check coding standards:

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-check

docker run --rm --interactive --tty --volume ${PWD}:/app node:20 yarn --cwd /app install
docker run --rm --interactive --tty --volume ${PWD}:/app node:20 yarn --cwd /app coding-standards-check
```

Apply coding standards:

```shell
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-apply

docker run --rm --interactive --tty --volume ${PWD}:/app node:20 yarn --cwd /app coding-standards-apply
```
