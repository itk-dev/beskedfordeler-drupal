<?php

namespace Drupal\beskedfordeler\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Abstract event subscriber for PostStatusBeskedModtagEvent.
 */
abstract class AbstractEventSubscriber implements EventSubscriberInterface {
  use LoggerAwareTrait;

  /**
   * The unique event subscriber id.
   *
   * @var string
   */
  protected static string $id;

  /**
   * The module settings.
   *
   * @var array
   *
   * @phpstan-var array<string, mixed>
   */
  protected array $settings;

  /**
   * Constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->setLogger($logger);
    $this->settings = (array) Settings::get('beskedfordeler', []);
    if (!isset(static::$id)) {
      throw new \RuntimeException(sprintf('Missing event subscriber id in %s', static::class));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PostStatusBeskedModtagEvent::class => 'postStatusBeskedModtag',
    ];
  }

  /**
   * Event subscriber callback.
   */
  public function postStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    try {
      if ($this->isEnabled()) {
        $this->process($event);
      }
    }
    catch (\Throwable $throwable) {
      $this->logger->error($throwable->getMessage(), ['throwable' => $throwable]);
    }
  }

  /**
   * Get settings for this subscriber.
   *
   * @return array
   *   The settings.
   *
   * @phpstan-return array<string, mixed>
   */
  protected function getSettings(): array {
    return $this->settings['event_subscriber'][static::$id] ?? [];
  }

  /**
   * Decide if this subscriber is enabled.
   */
  protected function isEnabled(): bool {
    return (bool) ($this->getSettings()['enabled'] ?? FALSE);
  }

  /**
   * Process the event.
   */
  abstract protected function process(PostStatusBeskedModtagEvent $event): void;

}
