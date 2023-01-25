<?php

namespace Drupal\beskedfordeler\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Abstract event subscriber for PostStatusBeskedModtagEvent.
 */
abstract class AbstractBeskedfordelerEventSubscriber implements EventSubscriberInterface {
  use LoggerAwareTrait;

  /**
   * Constructor.
   */
  public function __construct(LoggerInterface $logger) {
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
   * Event subscriber callback.
   */
  public function postStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    try {
      $this->processPostStatusBeskedModtag($event);
    }
    catch (\Throwable $throwable) {
      $this->logger->error($throwable->getMessage(), ['throwable' => $throwable]);
    }
  }

  /**
   * Process the event.
   */
  protected function processPostStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {

  }

}
