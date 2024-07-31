<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Drupal\investigation_builder\Services\InvestigationBuilderService\InvestigationBuilderService;
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
 *     "create" = "/rest/investigation/list/add",
 *	   "delete" = "/rest/investigation/list/delete/{investigationId}"
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
   * The investigation builder service.
   */
  protected InvestigationBuilderService $investigationBuilderService;

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
    InvestigationBuilderService $investigation_builder_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('get_investigation_list_resource');
    $this->currentUser = $currentUser;
    $this->investigationBuilderService = $investigation_builder_service;
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
      $container->get('investigation_builder.service')
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

      //    $term_name = $data['revision_status'];  //have to fix this
      //      $term = $this->setRevisionStatus($term_name);

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
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    try {
      $response = $this->investigationBuilderService->loadInvestigationBuilderList();
      return new ResourceResponse($response, 200);
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while loading InvestigationBuilder list: @message', ['@message' => $e->getMessage()]);
      throw new HttpException(500, 'Internal Server Error');
    }
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
   *
   * @param string $investigationId
   *   The ID of the investigation entity to delete.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the specified entity does not exist.
   */
  public function delete($investigationId): ModifiedResourceResponse {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $investigation = InvestigationBuilder::load($investigationId);
    if (!$investigation) {
      throw new NotFoundHttpException(sprintf('Investigation with ID %s was not found.', $investigationId));
    }

    try {
      $investigation->delete();
      $this->logger->notice('Deleted Investigation entity with ID @id.', ['@id' => $investigationId]);

      return new ModifiedResourceResponse(NULL, 204);
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while deleting Investigation entity with ID @id: @message', [
        '@id' => $investigationId,
        '@message' => $e->getMessage(),
      ]);
      throw new HttpException(500, 'Internal Server Error');
    }
  }
  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('investigationId', '\d+');
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
