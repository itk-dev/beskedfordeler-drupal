<?php

namespace Drupal\beskedfordeler\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Event subscriber for storing PostStatusBeskedModtagEvent in database.
 */
class DatabaseEventSubscriber extends AbstractEventSubscriber {
  /**
   * {@inheritdoc}
   */
  protected static string $id = 'database';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, LoggerInterface $logger) {
    parent::__construct($logger);
    $this->database = $database;
  }

  /**
   * Process the event.
   */
  public function process(PostStatusBeskedModtagEvent $event): void {
    $this->database
      ->insert('beskedfordeler_message')
      ->fields([
        'created' => $event->createdAt,
        'type' => 'PostStatusBeskedModtag',
        'message' => $event->document->saveXML(),
      ])
      ->execute();
  }

}
