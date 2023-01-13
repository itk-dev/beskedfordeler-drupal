<?php

namespace Drupal\beskedfordeler\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;

/**
 * Event subscriber for logging PostStatusBeskedModtagEvent in Drupal log.
 */
class LoggerEventSubscriber extends AbstractEventSubscriber {
  /**
   * {@inheritdoc}
   */
  protected static string $id = 'logger';

  /**
   * Process the event.
   */
  public function process(PostStatusBeskedModtagEvent $event): void {
    $this->logger->debug($event->document->saveXML(), [
      'event' => $event,
    ]);
  }

}
