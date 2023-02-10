<?php

namespace Drupal\beskedfordeler_forward\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\beskedfordeler\EventSubscriber\AbstractBeskedfordelerEventSubscriber;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Event subscriber for forwarding Beskedfordeler messages.
 */
class ForwardEventSubscriber extends AbstractBeskedfordelerEventSubscriber {
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
    parent::__construct($logger);
    $this->client = $client;
    $this->requestStack = $requestStack;
  }

  /**
   * Process the event.
   */
  public function processPostStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    $request = $this->requestStack->getCurrentRequest();
    $options = [
      'body' => $event->getDocument()->saveXML(),
      'headers' => [
        'content-type' => 'application/xml',
        'x-beskedfordeler-forwarded-from' => $request->getUri(),
        'x-beskedfordeler-message-created-at' => (new \DateTimeImmutable('@' . $event->getCreatedAt()))->format(\DateTimeInterface::ATOM),
      ],
    ];
    foreach ($this->getEndpoints() as $endpoint) {
      try {
        $this->client->request('POST', $endpoint, $options);
        $this->logger->info('Message forwarded to @endpoint', ['@endpoint' => $endpoint]);
      }
      catch (\Exception $exception) {
        $this->logger->error('Error forwarding message to @endpoint: @message', [
          '@endpoint' => $endpoint,
          '@message' => $exception->getMessage(),
        ]);
      }
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
