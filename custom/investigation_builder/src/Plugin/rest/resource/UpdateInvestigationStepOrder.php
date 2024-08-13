<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\investigation_builder\Services\InvestigationStepBuilderService\InvestigationStepBuilderService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents update_investigation_step_order records as resources.
 *
 * @RestResource (
 *   id = "Update Investigation Step order",
 *   label = @Translation("Update Investigation Step Order"),
 *   uri_paths = {
 *     "canonical" = "/api/update-investigation-step-order/{investigationId}",
 *     "patch" = "/api/update-investigation-step-order/{investigationId}"
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
final class UpdateInvestigationStepOrder extends ResourceBase {

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
    InvestigationStepBuilderService $investigation_step_builder_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('update_investigation_step_order');
    $this->currentUser = $currentUser;
    $this->investigationStepBuilderService = $investigation_step_builder_service;
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
      $container->get('investigation_step_builder.service')
    );
  }


  public function patch($investigationId, $data): ModifiedResourceResponse{
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $entity = $this->investigationStepBuilderService->updateInvestigationStepOrder($investigationId, $data);

    $this->logger->notice('Reorder Investigation steps');
    return new ModifiedResourceResponse($entity, 200);
  }

}
