<?php

namespace Drupal\beskedfordeler\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Abstract event for BeskedModtag.
 */
abstract class AbstractBeskedModtagEvent extends Event {
  /**
   * The ModtagBeskedInput document.
   *
   * @var \DOMDocument
   */
  public \DOMDocument $document;

  /**
   * Creation time.
   *
   * @var int
   */
  public int $createdAt;

  /**
   * Constructs the object.
   *
   * @param \DOMDocument $document
   *   The ModtagBeskedInput document.
   * @param int $createdAt
   *   When the message was received.
   */
  public function __construct(\DOMDocument $document, int $createdAt) {
    $this->document = $document;
    $this->createdAt = $createdAt;
  }

}
