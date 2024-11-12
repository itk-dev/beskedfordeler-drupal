<?php

namespace Drupal\beskedfordeler_database\Helper;

use Drupal\Core\Database\Connection;
use Drupal\beskedfordeler\Event\AbstractBeskedModtagEvent;
use Drupal\beskedfordeler\Exception\InvalidMessageException;
use Drupal\beskedfordeler_database\Model\Message;
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
  public function saveMessage(AbstractBeskedModtagEvent $event): string {
    $message = $event->getDocument()->saveXML();
    $messageId = $this->getMessageId($message);

    $this->database
      ->upsert(self::TABLE_NAME)
      ->fields([
        'created',
        'type',
        'message',
        'message_id',
      ])
      ->key('message_id')
      ->values([
        'created' => $event->getCreatedAt(),
        'type' => $event->getType(),
        'message' => $message,
        'message_id' => $messageId,
      ])
      ->execute();

    return $messageId;
  }

  /**
   * Load messages.
   *
   * @param string|null $type
   *   The message type.
   *
   * @return array|Message[]
   *   The messages.
   */
  public function loadMessages(?string $type = NULL): array {
    $query = $this->database
      ->select(self::TABLE_NAME, 'm')
      ->fields('m');
    if (NULL !== $type) {
      $query->condition('type', $type);
    }

    return $query
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_CLASS, Message::class);
  }

  /**
   * Load message by id.
   *
   * @param string $id
   *   The message id.
   *
   * @return \Drupal\beskedfordeler_database\Model\Message|null
   *   The message if any.
   */
  public function loadMessage(string $id): ?Message {
    return $this->database
      ->select(self::TABLE_NAME, 'm')
      ->fields('m')
      ->condition('message_id', (string) $id)
      ->execute()
      ->fetchObject(Message::class, []) ?: NULL;
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
          'message_id' => [
            'description' => 'The message UUID (formatted with dashes).',
            'type' => 'varchar',
            'length' => 36,
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
        'primary key' => ['message_id'],
      ],
    ];
  }

  /**
   * Implement hook_update_N().
   */
  public function update9001($sandbox) {
    $messages = $this->database
      ->select(self::TABLE_NAME, 'm')
      ->fields('m')
      ->execute()
      ->fetchAll();

    $messageIdFieldName = 'message_id';
    $spec = $this->schema()[self::TABLE_NAME]['fields'][$messageIdFieldName];
    // Allow null values temporarily.
    $spec['not null'] = FALSE;
    $schema = $this->database->schema();
    $schema->addField(self::TABLE_NAME, $messageIdFieldName, $spec);

    $messageIds = [];

    // Set message_id on existing messages and remove duplicate messages.
    foreach ($messages as $message) {
      $messageId = $this->getMessageId($message->message);
      if (isset($messageIds[$messageId])) {
        // Delete duplicate message.
        $this->database
          ->delete(self::TABLE_NAME)
          ->condition('id', $message->id)
          ->execute();
      }
      else {
        $this->database
          ->update(self::TABLE_NAME)
          ->fields([
            $messageIdFieldName => $messageId,
          ])
          ->condition('id', $message->id)
          ->execute();
      }
      $messageIds[$messageId] = $messageId;
    }

    // Drop old primary key field.
    $schema->dropField(self::TABLE_NAME, 'id');
    $schema->dropPrimaryKey(self::TABLE_NAME);

    // Finalize message_id field and set it a primary key.
    $spec = $this->schema()[self::TABLE_NAME]['fields'][$messageIdFieldName];
    $schema->changeField(self::TABLE_NAME, $messageIdFieldName, $messageIdFieldName, $spec);
    $schema->addPrimaryKey(self::TABLE_NAME, [$messageIdFieldName]);
  }

  /**
   * Get message id from Beskedfordeler message.
   */
  private function getMessageId(string $message): string {
    $document = new \DOMDocument();
    if (@$document->loadXML($message)) {
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('ns2', 'urn:oio:besked:kuvert:1.0');
      $xpath->registerNamespace('default', 'urn:oio:sagdok:3.0.0');
      $element = $xpath->query('//ns2:BeskedId/default:UUIDIdentifikator')->item(0);

      if (NULL !== $element) {
        return $element->nodeValue;
      }

      throw new InvalidMessageException('Cannot find message id');
    }
  }

}
