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
 * Represents delete_investigation records as resources.
 *
 * @RestResource (
 *   id = "delete_investigation_resource",
 *   label = @Translation("Delete Investigation resource"),
 *   uri_paths = {
 *      "canonical" = "/rest/delete-investigation-resource/{investigationId}",
 *     "delete" = "/rest/delete-investigation-resource/{investigationId}"
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
final class DeleteInvestigationResource extends ResourceBase {

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
    InvestigationBuilderService $investigation_builder_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('delete_investigation_resource');
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
      $container->get(('current_user')),
      $container->get('investigation_builder.service')
    );
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

    try {
      $this->investigationBuilderService->deleteInvestigation($investigationId);
      return new ModifiedResourceResponse(NULL, 204);
    } catch (\Exception $e) {
      $this->logger->error('An error occurred while deleting Investigation entity with ID @id: @message', ['@id' => $investigationId, '@message' => $e->getMessage(),]);
      throw new HttpException(500, 'Internal Server Error');
    }
    return new ModifiedResourceResponse(NULL, 204);
  }

}
