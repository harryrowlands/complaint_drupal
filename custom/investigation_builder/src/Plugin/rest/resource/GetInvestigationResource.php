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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents Get Investigation Resource records as resources.
 *
 * @RestResource (
 *   id = "get_investigation_resource",
 *   label = @Translation("Get Investigation Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/get-investigation-resource/{investigationId}",
 *     "create" = "/api/get-investigation-resource/{investigationId}",
 *   "patch" = "/api/get-investigation-resource/update/{investigationId}",
 *   "delete" = "/api/get-investigation-resource/{investigationId}"
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
final class GetInvestigationResource extends ResourceBase {

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
    $this->storage = $keyValueFactory->get('get_investigation_resource');
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
   * Responds to POST requests and saves a new step for the investigation.
   *
   * @param array $data
   *   The data containing information about the new step.
   *
   * @return ModifiedResourceResponse
   *   The HTTP response object indicating success or failure of the operation.
   *
   * @throws AccessDeniedHttpException
   *   Thrown if the current user does not have permission to access content.
   */
  public function post(array $data): ModifiedResourceResponse {
    // check permissions.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $investigation = InvestigationBuilder::load($data['investigationId']);

    // check if the investigation exists.
    if (!$investigation) {
      throw new NotFoundHttpException('Investigation not found.');
    }

  // have to implement the logic

    // Log the operation.
    $this->logger->notice('Created new step for investigation @investigationId.', [
      '@investigationId' => $investigation->id(),
    ]);

    // Return the response indicating success.
    return new ModifiedResourceResponse($investigation->toArray(), 201);
  }

  /**
   * Responds to GET requests.
   *
   * @param string $investigationId
   *
   *
   */
  public function get($investigationId): JsonResponse
  {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $investigation = InvestigationBuilder::load($investigationId);
    $returnValue = $investigation->getJsonString();

    return new JsonResponse($returnValue, 200, [], true);
  }
  /**
   * Responds to PATCH requests to update an step.
   *
   * @param string $investigationId
   *   The ID of the step to update.
   * @param array $data
   *   The updated data for the step.
   *
   * @return ModifiedResourceResponse
   *   The HTTP response object indicating success or failure of the operation.
   *
   * @throws NotFoundHttpException
   *   Thrown if the step with the provided ID is not found.
   * @throws AccessDeniedHttpException
   *   Thrown if the current user does not have permission to update steps.
   */
  public function patch($investigationId, array $data): ModifiedResourceResponse {
    // Check permissions.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $investigation = InvestigationBuilder::load($investigationId);

    if (!$investigation) {
      throw new NotFoundHttpException('Investigation not found.');
    }


    $jsonString = $investigation->getJsonString();
    //$data = json_decode($jsonString, true);

    $investigation->setJsonString($jsonString);
    $investigation->save();

    $this->logger->notice('Added new step  to Investigation with ID @id.', [
      '@id' => $investigationId

    ]);

// Return the response indicating success.
    return new ModifiedResourceResponse($investigation->toArray(), 200);
  }
  /**
   * Responds to DELETE requests.
   */
  public function delete($investigationId): ModifiedResourceResponse {
    // Check permissions.
    if (!$this->currentUser->hasPermission('delete investigation')) {
      throw new AccessDeniedHttpException();
    }

    // load the investigation entity.
    $investigation = InvestigationBuilder::load($investigationId);

    // check if the investigation exists.
    if (!$investigation) {
      throw new NotFoundHttpException(sprintf('Investigation with ID %s was not found.', $investigationId));
    }

    try {
      // Perform deletion.
      $investigation->delete();
      $this->logger->notice('Deleted Investigation entity with ID @id.', ['@id' => $investigationId]);

      //return response indicating successful deletion.
      return new ModifiedResourceResponse(NULL, 204);
    } catch (\Exception $e) {
      // log any errors that occur during deletion.
      $this->logger->error('An error occurred while deleting Investigation entity with ID @id: @message', [
        '@id' => $investigationId,
        '@message' => $e->getMessage(),
      ]);
      // Throw HTTP exception to indicate failure.
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
