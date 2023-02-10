<?php

namespace Drupal\beskedfordeler\Event;

use Drupal\beskedfordeler\Exception\InvalidEventException;
use Drupal\Component\EventDispatcher\Event;

/**
 * Abstract event for BeskedModtag.
 */
abstract class AbstractBeskedModtagEvent extends Event {
  /**
   * The message type.
   *
   * @var string
   */
  protected static string $type;

  /**
   * The ModtagBeskedInput document.
   *
   * @var \DOMDocument
   */
  protected \DOMDocument $document;

  /**
   * Creation time.
   *
   * @var int
   */
  protected int $createdAt;

  /**
   * Constructs the object.
   *
   * @param \DOMDocument $document
   *   The ModtagBeskedInput document.
   * @param int $createdAt
   *   When the message was received.
   */
  public function __construct(\DOMDocument $document, int $createdAt) {
    if (!isset(static::$type)) {
      throw new InvalidEventException(sprintf('Missing type in %s', get_class($this)));
    }
    $this->document = $document;
    $this->createdAt = $createdAt;
  }

  /**
   * Get type.
   */
  public function getType(): string {
    return static::$type;
  }

  /**
   * Get document.
   */
  public function getDocument(): \DOMDocument {
    return $this->document;
  }

  /**
   * Get created at.
   */
  public function getCreatedAt(): int {
    return $this->createdAt;
  }

}
