<?php

namespace Drupal\beskedfordeler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\beskedfordeler\Helper\MessageHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Beskedfordeler callbacks.
 */
class Controller extends ControllerBase {
  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * The message helper.
   *
   * @var \Drupal\beskedfordeler\Helper\MessageHelper
   */
  private MessageHelper $messageHelper;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $requestStack, MessageHelper $messageHelper, LoggerInterface $logger) {
    $this->requestStack = $requestStack;
    $this->messageHelper = $messageHelper;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('request_stack'),
      $container->get(MessageHelper::class),
      $container->get('logger.channel.beskedfordeler')
    );
  }

  /**
   * Handle PostStatusBeskedModtag.
   *
   * @see https://digitaliseringskataloget.dk/integration/sf1601
   */
  public function postStatusBeskedModtag(): Response {
    try {
      $request = $this->requestStack->getCurrentRequest();
      $message = $request->getContent();

      if ('POST' === $request->getMethod()) {
        $this->messageHelper->dispatch(
          MessageHelper::MESSAGE_TYPE_POST_STATUS_BESKED_MODTAG,
          $message,
          $request->server->get('REQUEST_TIME')
        );
      }

      $statusCode = 20;
      $errorMessage = NULL;

      return $this->buildResponse($statusCode, $errorMessage);
    }
    catch (\Throwable $throwable) {
      $this->logger->error($throwable->getMessage(), [
        'throwable' => $throwable,
      ]);

      return $this->buildResponse(40, $throwable->getMessage());
    }
  }

  /**
   * Build response.
   *
   * @see self::buildResponseDocument()
   */
  private function buildResponse(int $statusCode, ?string $errorMessage = NULL): Response {
    $document = $this->buildResponseDocument($statusCode, $errorMessage);

    $status = Response::HTTP_OK;
    $headers = ['content-type' => 'application/xml'];
    $content = $document->saveXML();

    return new Response($content, $status, $headers);
  }

  /**
   * Build Outputdokument.
   *
   * @param int $statusCode
   *   The status code.
   * @param string|null $errorMessage
   *   The error message if any.
   *
   * @return \DOMDocument
   *   The Outputdokument.
   */
  private function buildResponseDocument(int $statusCode, ?string $errorMessage = NULL): \DOMDocument {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<ns2:ModtagBeskedOutput xmlns="urn:oio:sagdok:3.0.0" xmlns:ns2="urn:oio:sts:1.0.0">
 <StandardRetur>
  <StatusKode/>
  <FejlbeskedTekst/>
 </StandardRetur>
</ns2:ModtagBeskedOutput>
XML;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('default', 'urn:oio:sagdok:3.0.0');

    $xpath->query('//default:StatusKode')->item(0)->nodeValue = $statusCode;
    $xpath->query('//default:FejlbeskedTekst')->item(0)->nodeValue = $errorMessage;

    return $document;
  }

}
