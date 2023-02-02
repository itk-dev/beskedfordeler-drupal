<?php

namespace Drupal\beskedfordeler_database\Helper;

use Drupal\beskedfordeler\Event\AbstractBeskedModtagEvent;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * The little helper.
 */
final class Helper {
  use LoggerAwareTrait;

  private const TABLE_NAME = 'beskedfordeler_database_message';

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, LoggerInterface $logger) {
    $this->database = $database;
    $this->setLogger($logger);
  }

  /**
   * Save message from event in database.
   */
  public function saveMessage(AbstractBeskedModtagEvent $event): ?int {
    return $this->database
      ->insert(self::TABLE_NAME)
      ->fields([
        'created' => $event->getCreatedAt(),
        'type' => $event->getType(),
        'message' => $event->getDocument()->saveXML(),
      ])
      ->execute();
  }

  /**
   * Load messages.
   *
   * @param string|null $type
   *   The message type.
   * @param bool $distinct
   *   If set, only distinct messages are loaded.
   *
   * @return array
   *   The message objects.
   *
   * @phpstan-return array<int, object>
   */
  public function loadMessages(string $type = NULL, bool $distinct = FALSE): array {
    $query = $this->database
      ->select(self::TABLE_NAME, 'm')
      ->fields('m');
    if (NULL !== $type) {
      $query->condition('type', $type);
    }

    if ($distinct) {
      $query->addExpression('min(id)', 'id');
      $query->groupBy('message');
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
      ->select(self::TABLE_NAME, 'm')
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
      ->delete(self::TABLE_NAME)
      ->execute();
  }

  /**
   * Implements hook_schema().
   *
   * @phpstan-return array<string, mixed>
   */
  public function schema(): array {
    return [
      self::TABLE_NAME => [
        'description' => 'Beskedfordeler message',
        'fields' => [
          'id' => [
            'description' => 'The primary identifier.',
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
          'created' => [
            'description' => 'The Unix timestamp when the message was created.',
            'type' => 'int',
            'not null' => TRUE,
          ],
          'type' => [
            'description' => 'The type of message.',
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
          ],
          'message' => [
            'description' => 'The message.',
            'type' => 'text',
            'size' => 'medium',
            'not null' => TRUE,
          ],
        ],
        'indexes' => [
          'created' => ['created'],
          'type' => ['type'],
        ],
        'primary key' => ['id'],
      ],
    ];
  }

}
