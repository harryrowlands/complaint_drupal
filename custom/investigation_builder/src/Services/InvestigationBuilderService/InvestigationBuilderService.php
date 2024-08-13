<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
/**
 * Service Class for handling InvestigationBuilder.
 */
final class InvestigationBuilderService implements InvestigationBuilderServiceInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an InvestigationBuilderService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this ->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */

  public function createInvestigation(array $data) {
    $entity = $this->entityTypeManager->getStorage('investigation_builder')->create($data);
    $entity->save();
    $this->logger->notice('Created new InvestigationBuilder entity with ID @id.', ['@id' => $entity->id()]);
    return $entity;
  }


  public function loadInvestigationBuilderList() {

    $unformattedInvestigations = InvestigationBuilder::loadMultiple();
    $investigationList = array();
    foreach ($unformattedInvestigations as $unformattedInvestigation) {
      if ($unformattedInvestigation instanceof InvestigationBuilder) {
        $investigation_builder['label'] = $unformattedInvestigation->getName();
        $investigation_builder['entityId'] = $unformattedInvestigation->id();
        $investigation_builder['revisionId'] = $unformattedInvestigation->getRevisionId();
        $investigation_builder['revisionCreationTime'] = $unformattedInvestigation->getRevisionCreationTime();
        $investigation_builder['createdTime'] = $unformattedInvestigation->getCreatedTime();
        $investigation_builder['revisionStatus'] = $unformattedInvestigation->getRevisionStatus();


        $investigation_builder['json_string'] = $unformattedInvestigation->getJsonString();

        $investigationList[] = $investigation_builder;
        unset($investigation_builder);
      }
    }

    return $investigationList;
  }

}
