<?php

namespace Drupal\beskedfordeler_forward\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for forwarding Beskedfordeler messages.
 */
class ForwardEventSubscriber implements EventSubscriberInterface {
  use LoggerAwareTrait;

  /**
   * The client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private ClientInterface $client;

  /**
   * The request stack.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $client, RequestStack $requestStack, LoggerInterface $logger) {
    $this->client = $client;
    $this->requestStack = $requestStack;
    $this->setLogger($logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PostStatusBeskedModtagEvent::class => 'postStatusBeskedModtag',
    ];
  }

  /**
   * Process the event.
   */
  public function postStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    try {
      $request = $this->requestStack->getCurrentRequest();
      $options = [
        'body' => $event->document->saveXML(),
        'headers' => [
          'content-type' => 'application/xml',
          'x-beskedfordeler-forwarded-from' => $request->getUri(),
        ],
      ];
      foreach ($this->getEndpoints() as $endpoint) {
        $this->client->request('POST', $endpoint, $options);
        $this->logger->info('Message forwarded to @endpoint', ['@endpoint' => $endpoint]);
      }
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
    }
  }

  /**
   * Get endpoints.
   */
  private function getEndpoints(): array {
    $settings = Settings::get('beskedfordeler_forward');

    return $settings['endpoints'] ?? [];
  }

}
