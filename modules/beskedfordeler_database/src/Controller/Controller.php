<?php

namespace Drupal\beskedfordeler_database\Controller;

use Drupal\beskedfordeler_database\Helper\Helper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Messages controller.
 */
class Controller extends ControllerBase {
  /**
   * The helper.
   *
   * @var \Drupal\beskedfordeler_database\Helper\Helper
   */
  private Helper $helper;

  /**
   * Constructor.
   */
  public function __construct(Helper $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(Helper::class)
    );
  }

  /**
   * Index action.
   */
  public function index(): array {
    $messages = $this->helper->loadMessages();

    $build['messages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Id'),
        $this->t('Created at'),
        $this->t('Type'),
        $this->t('Data'),
      ],
    ];

    foreach ($messages as $index => $message) {
      $showMessageUrl = Url::fromRoute('beskedfordeler_database.show', ['id' => $message->id]);
      $build['messages'][$index] = [
        'id' => (new Link(
          $message->id,
          $showMessageUrl
        ))->toRenderable(),
        'created_at' => [
          '#markup' => DrupalDateTime::createFromTimestamp($message->created)->format(DrupalDateTime::FORMAT),
        ],
        'type' => [
          '#markup' => $message->type,
        ],
        'data' => [
          '#markup' => '<pre><code>' . htmlspecialchars($message->getDataXml() ?? '👻') . '</code></pre>',
        ],
      ];
    }

    return $build;
  }

  /**
   * Show action.
   */
  public function show(int $id): array {
    $message = $this->helper->loadMessage($id);
    if (NULL === $message) {
      throw new NotFoundHttpException('Message not found');
    }

    $build['list'] = [
      '#type' => 'html_tag',
      '#tag' => 'dl',
    ];

    $addListItem = static function ($label, $value) use (&$build) {
      $build['list'][] = [
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'dt',
          '#value' => $label,
        ],
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'dd',

          '#value' => $value,
        ],
      ];
    };

    $addListItem($this->t('Created at'), DrupalDateTime::createFromTimestamp($message->created)->format(DrupalDateTime::FORMAT));
    $addListItem($this->t('Type'), $message->type);
    $addListItem($this->t('Data'), '<pre><code>' . htmlspecialchars($message->getDataXml() ?? '👻') . '</code></pre>');
    $addListItem($this->t('Full message'), '<pre><code>' . htmlspecialchars($message->message) . '</code></pre>');

    return $build;
  }

  /**
   * Title callback.
   */
  public function titleShow(int $id) {
    return $this->t('Beskedfordeler message #:id', [':id' => $id]);
  }

}
