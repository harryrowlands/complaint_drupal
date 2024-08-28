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
use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;


/**
 * Represents create_report_builder records as resources.
 *
 * @RestResource (
 *   id = "create_report_builder_resource",
 *   label = @Translation("create_report_builder"),
 *   uri_paths = {
 *     "canonical" = "/rest/report/create",
 *     "create" = "/rest/report/create"
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
final class CreateReportBuilderResource extends ResourceBase {

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
    $this->storage = $keyValueFactory->get('create_report_builder_resource');
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
   * Returns next available ID.
   */
  private function getNextId(): int {
    $ids = \array_keys($this->storage->getAll());
    return count($ids) > 0 ? max($ids) + 1 : 1;
  }
  
  /**
   * Responds to POST requests and saves the new record.
   */
  public function post($data): ModifiedResourceResponse {
    try {
      // -- Are you authenticated?
      if (!$this->currentUser->hasPermission('access content')) {
        throw new AccessDeniedHttpException();
      }

      $this->logger->notice('Data received: @data', ['@data' => json_encode($data)]);
      
      // -- Do some tests
      $processId = $data['investigation_id'];
      $this->logger->notice('Investigation ID: @id', ['@id' => $processId]);
      $investigation = InvestigationBuilder::load($processId);

      $newInvestigationJson = $investigation->getJsonString();
      $newInvestigationData = json_decode($newInvestigationJson, true);

      $report = ReportBuilder::create($data);
      $entityid = $report->save();
      $returnValue['entityId'] = $report->id();
      $jsonstring = [
        'entityId' =>$report->id(),
        'reportLabel' =>$report->label(),
        'investigationId' =>$data['investigation_id'],
        'steps'=> $newInvestigationData['steps'],
      ];
      $reportJsonString = json_encode($jsonstring);
      $report->setJsonString($reportJsonString);
      $entityid = $report->save();
      // log the creation of the entity.
      $this->logger->notice('Created new report entity with ID @id.', ['@id' => $entityid]);
      return new ModifiedResourceResponse($report, 201);

      
      // -- DIVIDER
      // create a new instance of the entity.
      //$report = ReportBuilder::create($data);
      //$entityid = $report->save();
      //$returnValue['entityId'] = $report->id();
      //$jsonstring = [
      //  'entityId' =>$report->id(),
      //  'reportLabel' =>$report->label(),
      //  'investigationId' =>$data['investigation_id']
      //];
      //$reportJsonString = json_encode($jsonstring);
      //$report->setJsonString($reportJsonString);
      //$entityid = $report->save();
      //// log the creation of the entity.
      //$this->logger->notice('Created new report entity with ID @id.', ['@id' => $entityid]);
      //return new ModifiedResourceResponse($report, 201);
    } catch (\Exception $e) {
      // handle any exceptions that occur during entity creation.
      $this->logger->error('An error occurred while creating the new investigation entity: @message', ['@message' => $e->getMessage()]);
      throw new HttpException(500, 'Internal Server Error');
    }
  }

}