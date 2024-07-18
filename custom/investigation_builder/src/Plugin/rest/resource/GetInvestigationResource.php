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
 *   "patch" = "/api/update-assessment/{assessmentId}"
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
   * Responds to POST requests and saves a new assessment for the investigation.
   *
   * @param array $data
   *   The data containing information about the new assessment.
   *
   * @return ModifiedResourceResponse
   *   The HTTP response object indicating success or failure of the operation.
   *
   * @throws AccessDeniedHttpException
   *   Thrown if the current user does not have permission to access content.
   */
  public function post(array $data): ModifiedResourceResponse {
    // Check permissions.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Validate incoming data - Implement your validation logic here.

    // Retrieve the investigation entity.
    $investigation = InvestigationBuilder::load($data['investigationId']);

    // Check if the investigation exists.
    if (!$investigation) {
      throw new NotFoundHttpException('Investigation not found.');
    }

    // Create a new assessment entity.
    $assessment = new AssessmentEntity();
    $assessment->setLabel($data['assessmentLabel']);
    $assessment->setDescription($data['description']);
    // Set other fields based on your entity structure.

    // Save the assessment entity.
    $assessment->save();

    // Optionally, associate the assessment with the investigation.
    $investigation->addAssessment($assessment);
    $investigation->save();

    // Log the operation.
    $this->logger->notice('Created new assessment @id for investigation @investigationId.', [
      '@id' => $assessment->id(),
      '@investigationId' => $investigation->id(),
    ]);

    // Return the response indicating success.
    return new ModifiedResourceResponse($assessment->toArray(), 201);
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
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
//    if (!$this->storage->has(investigationId)) {
//      throw new NotFoundHttpException();
//    }
//    $resource = $this->storage->get($id);
//    return new ResourceResponse($resource);
    $investigation = InvestigationBuilder::load($investigationId);
    $returnValue = $investigation->getJsonString();

    return new JsonResponse($returnValue, 200, [], true);
  }
  /**
   * Responds to PATCH requests to update an assessment.
   *
   * @param string $assessmentId
   *   The ID of the assessment to update.
   * @param array $data
   *   The updated data for the assessment.
   *
   * @return ModifiedResourceResponse
   *   The HTTP response object indicating success or failure of the operation.
   *
   * @throws NotFoundHttpException
   *   Thrown if the assessment with the provided ID is not found.
   * @throws AccessDeniedHttpException
   *   Thrown if the current user does not have permission to update assessments.
   */
  public function patch($assessmentId, array $data): ModifiedResourceResponse {
    // Check permissions.
    if (!$this->currentUser->hasPermission('administer assessments')) {
      throw new AccessDeniedHttpException();
    }

    // Load the assessment entity.
    $assessment = AssessmentEntity::load($assessmentId);

    // Check if the assessment exists.
    if (!$assessment) {
      throw new NotFoundHttpException('Assessment not found.');
    }

    // Update the assessment fields based on incoming data.
    if (isset($data['assessmentLabel'])) {
      $assessment->setLabel($data['assessmentLabel']);
    }
    if (isset($data['description'])) {
      $assessment->setDescription($data['description']);
    }
    // Add more fields as needed.

    // Save the updated assessment entity.
    $assessment->save();

    // Log the operation.
    $this->logger->notice('Updated assessment @id.', ['@id' => $assessment->id()]);

    // Return the response indicating success.
    return new ModifiedResourceResponse($assessment->toArray(), 200);
  }
  /**
   * Responds to DELETE requests.
   */
  public function delete($id): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $this->storage->delete($id);
    $this->logger->notice('The get investigation resource record @id has been deleted.', ['@id' => $id]);
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
