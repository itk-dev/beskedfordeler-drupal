<?php

namespace Drupal\beskedfordeler\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Subcriber for logging PostStatusBeskedModtagEvent in file system.
 */
class FileSystemEventSubscriber extends AbstractEventSubscriber {
  /**
   * {@inheritdoc}
   */
  protected static string $id = 'file_system';

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * Constructor.
   */
  public function __construct(FileSystemInterface $fileSystem, LoggerInterface $logger) {
    parent::__construct($logger);
    $this->fileSystem = $fileSystem;
  }

  /**
   * Process the event.
   */
  public function process(PostStatusBeskedModtagEvent $event): void {
    $settings = $this->getSettings();
    $directory = $settings['directory'] ?? $this->fileSystem->getTempDirectory();

    $filename = sprintf('beskedfordeler.%s.xml', (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM));
    if (!file_exists($directory)) {
      $this->fileSystem->mkdir($directory, NULL, TRUE);
    }
    if (!is_dir($directory)) {
      throw new \RuntimeException(sprintf('Error writing Beskedfordeler document: %s is not a directory', $directory));
    }
    $filename = $directory . '/' . $filename;

    $data = $event->document->saveXML();

    $filename = $this->fileSystem->saveData($data, $filename, FileSystemInterface::EXISTS_RENAME);

    if ($filename) {
      $this->logger->info('Beskedfordeler document written to file @filename', ['@filename' => $filename]);
    }
    else {
      throw new \RuntimeException(sprintf('Error writing Beskedfordeler document file %s', $filename));
    }
  }

}
