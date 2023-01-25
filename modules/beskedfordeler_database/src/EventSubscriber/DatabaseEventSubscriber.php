<?php

namespace Drupal\beskedfordeler_database\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\beskedfordeler\EventSubscriber\AbstractBeskedfordelerEventSubscriber;
use Drupal\beskedfordeler_database\Helper\Helper;
use Psr\Log\LoggerInterface;

/**
 * Event subscriber for storing PostStatusBeskedModtagEvent in database.
 */
class DatabaseEventSubscriber extends AbstractBeskedfordelerEventSubscriber {
  /**
   * The helper.
   *
   * @var \Drupal\beskedfordeler_database\Helper\Helper
   */
  private Helper $helper;

  /**
   * Constructor.
   */
  public function __construct(Helper $helper, LoggerInterface $logger) {
    parent::__construct($logger);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function processPostStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    $id = $this->helper->saveMessage($event);
    $this->logger->debug('Message saved to database (id: @id)', ['@id' => $id]);
  }

}
