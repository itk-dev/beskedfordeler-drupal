<?php

namespace Drupal\beskedfordeler\Helper;

use Drupal\beskedfordeler\Event\AbstractBeskedModtagEvent;
use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\beskedfordeler\Exception\InvalidMessageException;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Message helper.
 */
final class MessageHelper {
  use LoggerAwareTrait;

  public const MESSAGE_TYPE_POST_STATUS_BESKED_MODTAG = 'PostStatusBeskedModtag';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, Connection $database, LoggerInterface $logger) {
    $this->eventDispatcher = $eventDispatcher;
    $this->database = $database;
    $this->setLogger($logger);
  }

  /**
   * Get event type form message type.
   */
  private function getEventType(string $messageType): string {
    switch ($messageType) {
      case self::MESSAGE_TYPE_POST_STATUS_BESKED_MODTAG:
        return PostStatusBeskedModtagEvent::class;

      default:
        throw new InvalidMessageException(sprintf('Invalid message type: %s', $messageType));
    }
  }

  /**
   * Dispatch message event.
   */
  public function dispatch(string $type, string $message, int $createdAt): AbstractBeskedModtagEvent {
    $document = new \DOMDocument();
    if (!$document->loadXML($message)) {
      throw new InvalidMessageException('Invalid XML');
    }

    $eventType = $this->getEventType($type);
    $event = new $eventType($document, $createdAt);

    return $this->eventDispatcher->dispatch($event);
  }

  /**
   * Load messages.
   *
   * @param string|null $type
   *   The message type.
   *
   * @return array
   *   The message objects.
   *
   * @phpstan-return array<int, object>
   */
  public function loadMessages(string $type = NULL): array {
    $query = $this->database
      ->select('beskedfordeler_message', 'm')
      ->fields('m');
    if (NULL !== $type) {
      $query->condition('type', $type);
    }

    return $query
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Load message by id.
   *
   * @param int $id
   *   The message id.
   *
   * @return object
   *   The message if any.
   */
  public function loadMessage(int $id): ?object {
    return $this->database
      ->select('beskedfordeler_message', 'm')
      ->fields('m')
      ->condition('id', (string) $id)
      ->execute()
      ->fetchObject() ?: NULL;
  }

  /**
   * Purge messages.
   */
  public function purgeMessages(): int {
    return $this->database
      ->delete('beskedfordeler_message')
      ->execute();
  }

}
