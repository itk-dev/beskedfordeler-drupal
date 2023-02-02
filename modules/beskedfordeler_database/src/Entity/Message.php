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

  /**
   * Get decoded data as XML.
   *
   * @return string|null
   *   The XML.
   */
  public function getDataXml(): ?string {
    $document = new \DOMDocument();
    if (@$document->loadXML($this->message)) {
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('data', 'urn:besked:kuvert:1.0');
      if ($nodes = $xpath->query('//data:Base64')) {
        foreach ($nodes as $node) {
          assert($node instanceof \DOMElement);
          $data = new \DOMDocument();
          $data->formatOutput = TRUE;
          if (@$data->loadXML(base64_decode($node->nodeValue))) {
            return $data->saveXML();
          }
        }
      }
    }

    return NULL;
  }

}
