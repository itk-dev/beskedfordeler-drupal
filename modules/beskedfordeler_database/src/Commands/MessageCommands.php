<?php

namespace Drupal\beskedfordeler_database\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\beskedfordeler\Helper\MessageHelper;
use Drupal\beskedfordeler_database\Helper\Helper;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Drush commands file for Beskedfordeler database.
 */
final class MessageCommands extends DrushCommands {
  /**
   * The message helper.
   *
   * @var \Drupal\beskedfordeler\Helper\MessageHelper
   */
  private Helper $helper;

  /**
   * The message helper.
   *
   * @var \Drupal\beskedfordeler\Helper\MessageHelper
   */
  private MessageHelper $messageHelper;

  /**
   * Constructor.
   */
  public function __construct(Helper $helper, MessageHelper $messageHelper) {
    $this->helper = $helper;
    $this->messageHelper = $messageHelper;
  }

  /**
   * List messages.
   *
   * @param array $options
   *   The command options.
   *
   * @option type
   *  The message type.
   *
   * @command beskedfordeler:message:list
   * @usage beskedfordeler:message:list --help
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function list(
    array $options = [
      'type' => NULL,
    ],
  ): void {
    $messages = $this->helper->loadMessages($options['type'], $options['distinct']);

    foreach ($messages as $message) {
      $this->writeln(sprintf(
        '%s %s %s',
        $message->messageId,
        DrupalDateTime::createFromTimestamp($message->created)->format(DrupalDateTime::FORMAT),
        $message->type
      ));
    }
  }

  /**
   * Show message.
   *
   * @param string $id
   *   The message id.
   * @param array $options
   *   The command options.
   *
   * @option decode-data
   *  If set, the actual message data will be decoded.
   * @option data-only
   *  Show only decoded data (implies --decode-data).
   *
   * @command beskedfordeler:message:show
   * @usage beskedfordeler:message:show --help
   */
  public function show(
    string $id,
    array $options = [
      'decode-data' => FALSE,
      'data-only' => FALSE,
    ],
  ): void {
    $message = $this->helper->loadMessage($id);

    if (NULL === $message) {
      throw new RuntimeException(sprintf('Cannot load message with id %s.', $id));
    }
    else {
      $document = new \DOMDocument();
      $document->formatOutput = TRUE;
      $document->loadXML($message->message);

      $dataOnly = $options['data-only'];
      $decodeData = $dataOnly || $options['decode-data'];
      if ($decodeData) {
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('data', 'urn:besked:kuvert:1.0');
        if ($nodes = $xpath->query('//data:Base64')) {
          foreach ($nodes as $node) {
            assert($node instanceof \DOMElement);
            $data = new \DOMDocument();
            $data->formatOutput = TRUE;
            $data->loadXML(base64_decode($node->nodeValue));
            $content = $data->saveXML();
            if ($dataOnly) {
              $this->output()->write($content);
              return;
            }
            else {
              $node->replaceChild($document->createCDATASection($content), $node->firstChild);
            }
          }
        }
      }
      $this->output()->write($document->saveXML());
    }
  }

  /**
   * Dispatch message.
   *
   * @param string $id
   *   The message id.
   *
   * @command beskedfordeler:message:dispatch
   * @usage beskedfordeler:message:dispatch --help
   */
  public function dispatch(string $id): void {
    $message = $this->helper->loadMessage($id);

    if (NULL === $message) {
      throw new RuntimeException(sprintf('Cannot find message with id %s.', $id));
    }
    else {
      $event = $this->messageHelper->dispatch($message->type, $message->message, $message->created);
      $this->output()->writeln(sprintf('Message %s dispatched (%s)', $id, get_class($event)));
    }
  }

  /**
   * Purge all messages from database.
   *
   * @command beskedfordeler:message:purge
   * @usage beskedfordeler:message:purge --help
   */
  public function purge(): void {
    if ($this->confirm(dt('Purge all messages?'), Drush::affirmative())) {
      $count = $this->helper->purgeMessages();
      $this->writeln(sprintf('All messages (%d) purged.', $count));
    }
    else {
      $this->writeln('No messages purged.');
    }
  }

}
