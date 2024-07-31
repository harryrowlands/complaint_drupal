<?php

declare(strict_types=1);

namespace Drupal\report_builder\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\report_builder\Entity\ReportBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents get_report_builder records as resources.
 *
 * @RestResource (
 *   id = "get_report_builder_resource",
 *   label = @Translation("Get report builder resource."),
 *   uri_paths = {
 *     "canonical" = "/rest/report/list",
 *     "create" = "/rest/report/list"
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
final class GetReportBuilderResource extends ResourceBase {

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
    $this->storage = $keyValueFactory->get('get_report_builder_resource');
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

    $unformattedReports = ReportBuilder::loadMultiple();
    $reportList = array();
    foreach ($unformattedReports as $unformattedReport) {
      if ($unformattedReport instanceof ReportBuilder) {
        $report_builder['label'] = $unformattedReport->getName();
        $report_builder['entityId'] = $unformattedReport->id();
        $report_builder['revisionId'] = $unformattedReport->getRevisionId();
        $report_builder['uid'] = $unformattedReport->getUid();
        $report_builder['jsonString'] = $unformattedReport->getJsonString();
        $report_builder['revisionCreationTime'] = $unformattedReport->getRevisionCreationTime();
        $report_builder['revisionStatus'] = $unformattedReport->getRevisionStatus();

        $reportList[] = $report_builder;
        unset($report_builder);
      }
    }

    $response = new ResourceResponse($reportList);
    $response->addCacheableDependency($this->currentUser);
    return $response;
  }

}
