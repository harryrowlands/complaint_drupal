<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents get_investigation_list records as resources.
 *
 * @RestResource (
 *   id = "get_investigation_list_resource",
 *   label = @Translation("Get Investigation List"),
 *   uri_paths = {
 *     "canonical" = "/rest/investigation/list",
 *     "create" = "/rest/investigation/list"
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
final class GetInvestigationListResource extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

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
    AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('get_investigation_list_resource');
    $this->currentUser = $currentUser;
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
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(array $data): ModifiedResourceResponse {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    try {
      // create a new instance of the entity.
      $entity = InvestigationBuilder::create($data);
      $entity->save();

      // log the creation of the entity.
      $this->logger->notice('Created new InvestigationBuilder entity with ID @id.', ['@id' => $entity->id()]);


      return new ModifiedResourceResponse($entity, 201);
    } catch (\Exception $e) {
      // handle any exceptions that occur during entity creation.
      $this->logger->error('An error occurred while creating InvestigationBuilder entity: @message', ['@message' => $e->getMessage()]);
      throw new HttpException(500, 'Internal Server Error');
    }
  }

  /**
   * Responds to GET requests.
   *
   * @return ResourceResponse
   *    The HTTP response object.
   *
   */
  public function get()
  {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $unformattedInvestigations = InvestigationBuilder::loadMultiple();
    $investigationList = array();
    foreach ($unformattedInvestigations as $unformattedInvestigation) {
      if ($unformattedInvestigation instanceof InvestigationBuilder) {
        $investigation_builder['label'] = $unformattedInvestigation->getName();
        $investigation_builder['entityId'] = $unformattedInvestigation->id();
        $investigation_builder['revisionId'] = $unformattedInvestigation->getRevisionId();
        $investigation_builder['revisionCreationTime'] = $unformattedInvestigation->getRevisionCreationTime();
        $investigation_builder['revisionStatus'] = $unformattedInvestigation->getRevisionStatus();

        $investigationList[] = $investigation_builder;
        unset($investigation_builder);
      }
    }

    $response = new ResourceResponse($investigationList);
    $response->addCacheableDependency($this->currentUser);
    return $response;
  }

  /**
   * Responds to PATCH requests.
   */
  public function patch($id, array $data): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $stored_data = $this->storage->get($id);
    $data += $stored_data;
    $this->storage->set($id, $data);
    $this->logger->notice('The get_investigation_list record @id has been updated.', ['@id' => $id]);
    return new ModifiedResourceResponse($data, 200);
  }

  /**
   * Responds to DELETE requests.
   */
  public function delete($id): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $this->storage->delete($id);
    $this->logger->notice('The get_investigation_list record @id has been deleted.', ['@id' => $id]);
    // Deleted responses have an empty body.
    return new ModifiedResourceResponse(NULL, 204);
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
