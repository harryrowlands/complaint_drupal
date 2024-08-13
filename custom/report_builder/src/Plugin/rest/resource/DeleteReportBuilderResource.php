<?php

declare(strict_types=1);

namespace Drupal\report_builder\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents delete_report_builder records as resources.
 *
 * @RestResource (
 *   id = "delete_report_builder_resource",
 *   label = @Translation("delete_report_builder"),
 *   uri_paths = {
 *     "canonical" = "/rest/report/delete/{id}",
 *     "create" = "/rest/report/delete/{id}"
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

 final class DeleteReportBuilderResource extends ResourceBase {

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
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('delete_report_builder_resource');
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
      $container->get('keyvalue')
    );
  }

  /**
   * Responds to DELETE requests.
   */
  public function delete($id): ModifiedResourceResponse {
    // load the report entity with that id.
    $entity = \Drupal::entityTypeManager()->getStorage('report_builder')->load($id);
    
    $this->logger->info('Attempting to delete record with id: @id.', ['@id' => $id]);
    
    //Check if entity exists
    if (!$entity) {
        $this->logger->notice('No record found with id: @id.', ['@id' => $id]);
        throw new NotFoundHttpException();
    }
    
    //delete the entity
    $entity->delete();
    $this->logger->notice('The delete_report_builder record @id has been deleted.', ['@id' => $id]);
    
    // Deleted responses have an empty body.
    return new ModifiedResourceResponse(NULL, 204);
}
}