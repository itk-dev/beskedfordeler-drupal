<?php

namespace Drupal\beskedfordeler_database\Commands;

use Drupal\beskedfordeler\Helper\MessageHelper;
use Drupal\beskedfordeler_database\Helper\Helper;
use Drupal\Core\Datetime\DrupalDateTime;
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
  public function list(array $options = [
    'type' => NULL,
  ]): void {
    $messages = $this->helper->loadMessages($options['type']);

    foreach ($messages as $message) {
      $this->writeln(sprintf(
        '% 8d %s %s',
        (int) $message->id,
        DrupalDateTime::createFromTimestamp($message->created)->format(DrupalDateTime::FORMAT),
        $message->type
      ));
    }
  }

  /**
   * Show message.
   *
   * @param int $id
   *   The message id.
   *
   * @command beskedfordeler:message:show
   * @usage beskedfordeler:message:show --help
   */
  public function show(int $id): void {
    $message = $this->helper->loadMessage($id);

    if (NULL === $message) {
      throw new RuntimeException(sprintf('Cannot find message with id %d.', $id));
    }
    else {
      $this->output()->write($message->message);
    }
  }

  /**
   * Dispatch message.
   *
   * @param int $id
   *   The message id.
   *
   * @command beskedfordeler:message:dispatch
   * @usage beskedfordeler:message:dispatch --help
   */
  public function dispatch(int $id): void {
    $message = $this->helper->loadMessage($id);

    if (NULL === $message) {
      throw new RuntimeException(sprintf('Cannot find message with id %d.', $id));
    }
    else {
      $event = $this->messageHelper->dispatch($message->type, $message->message, $message->created);
      $this->output()->writeln(sprintf('Message %d dispatched (%s)', $id, get_class($event)));
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
