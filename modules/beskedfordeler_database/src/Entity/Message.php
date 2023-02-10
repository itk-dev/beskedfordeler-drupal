<?php

namespace Drupal\beskedfordeler_database\Entity;

/**
 * A message.
 */
class Message {
  /**
   * The id.
   *
   * @var int
   */
  public int $id;

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

}
