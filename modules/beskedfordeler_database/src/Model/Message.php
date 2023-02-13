<?php

namespace Drupal\beskedfordeler_database\Model;

/**
 * A message.
 */
class Message {
  /**
   * The message id.
   *
   * @var string
   */
  public string $messageId;

  /**
   * The created timestamp.
   *
   * @var int
   */
  public int $created;

  /**
   * The type.
   *
   * @var string
   */
  public string $type;

  /**
   * The message.
   *
   * @var string
   */
  public string $message;

  /**
   * Called when using \PDO::FETCH_CLASS.
   */
  public function __set($name, $value) {
    $property = [
      'message_id' => 'messageId',
    ][$name] ?? $name;

    if (!property_exists($this, $property)) {
      throw new \RuntimeException(sprintf('Invalid property: %s', $property));
    }

    $this->$property = $value;
  }

}
