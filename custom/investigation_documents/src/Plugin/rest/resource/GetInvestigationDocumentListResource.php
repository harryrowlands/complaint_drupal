<?php

declare(strict_types=1);

namespace Drupal\investigation_documents\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\investigation_documents\Entity\InvestigationDocuments;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Represents Get Investigation Document List records as resources.
 *
 * @RestResource (
 *   id = "get_investigation_document_list_resource",
 *   label = @Translation("Get Investigation Document List"),
 *   uri_paths = {
 *     "canonical" = "/rest/investigation/document/get/{investigationId}",
 *     "create" = "/rest/investigation/document/get/"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
final class GetInvestigationDocumentListResource extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

  /**
   * The current user.
   */
  private AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    KeyValueFactoryInterface $keyValueFactory,
    AccountProxyInterface    $currentUser,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('get_investigation_document_resource');
    $this->currentUser = $currentUser;
     $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   */
  public function get($investigationId): ResourceResponse {

    if (!$this->currentUser->hasPermission('access content')) {
  throw new AccessDeniedHttpException();
    }

//    $investigationId = 123; // Replace with the actual investigationId you want to filter by.


//    $query = \Drupal::entityTypeManager('investigation_douments')
//      ->condition('status', 1) // 1 indicates published.
//    ->condition('investigationId', $investigationId)
//      ->accessCheck(TRUE); // Enable access checks.

    // Create an entity query for the investigation_documents entity.
    $query = $this->entityTypeManager
      ->getStorage('investigation_documents') // Get the storage handler.
      ->getQuery() // Create the query.
      ->condition('status', 1) // Add condition for published documents.
      ->condition('investigationId', $investigationId) // Add condition for matching investigationId.
     ->accessCheck(TRUE); // Enable access checks.

    $investigationDocumentIds = $query->execute();
    $unformattedInvestigationDocuments = InvestigationDocuments::loadMultiple($investigationDocumentIds);
        $investigationDocumentList = array();

        foreach ($unformattedInvestigationDocuments as $unformattedInvestigationDocument) {
          if ($unformattedInvestigationDocument instanceof InvestigationDocuments) {
            $document['label'] = $unformattedInvestigationDocument->getLabel();
            $document['entityId'] = $unformattedInvestigationDocument->id();
            $document['stepId'] = $unformattedInvestigationDocument->getStepId();
            $document['fileEntityId'] = $unformattedInvestigationDocument->getFileId();

            $investigationDocumentList[] = $document;
            unset($document);
          }
        }


        $response = new ResourceResponse($investigationDocumentList);
        $response->addCacheableDependency($this->currentUser);
        return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('id', '\d+');
    }
    return $route;
  }

  /**
   * Returns next available ID.
   */
  private function getNextId(): int {
    $ids = \array_keys($this->storage->getAll());
    return count($ids) > 0 ? max($ids) + 1 : 1;
  }

}