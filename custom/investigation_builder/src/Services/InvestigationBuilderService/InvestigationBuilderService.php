<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
  public function getInvestigationList() {

    $unformattedInvestigations = InvestigationBuilder::loadMultiple();
    $investigationList = array();
    foreach ($unformattedInvestigations as $unformattedInvestigation) {
      if ($unformattedInvestigation instanceof InvestigationBuilder) {
        $investigation_builder['label'] = $unformattedInvestigation->getName();
        $investigation_builder['entityId'] = $unformattedInvestigation->id();
        $investigation_builder['revisionId'] = $unformattedInvestigation->getRevisionId();
        $investigation_builder['revisionCreationTime'] = $unformattedInvestigation->getRevisionCreationTime();
        $investigation_builder['createdTime'] = $unformattedInvestigation->getCreatedTime();
        $investigation_builder['updatedTime'] = $unformattedInvestigation->getupdatedTime();
        $investigation_builder['revisionStatus'] = $unformattedInvestigation->getRevisionStatus();
        $investigation_builder['json_string'] = $unformattedInvestigation->getJsonString();

        $investigationList[] = $investigation_builder;
        unset($investigation_builder);
      }
    }

    return $investigationList;
  }

  public function createInvestigation(array $data) {

    $investigation = InvestigationBuilder::create($data);

    $entityId = $investigation->save();
    $returnValue['entityId'] = $investigation->id();
    $jsonstring = [
      'entityId' =>$investigation->id(),
      'uuid'=>uniqid(),
      'investigationLabel' =>$investigation->label(),
      'steps'=>[]
    ];
    $investigationJsonstring = json_encode($jsonstring);
    $investigation->setJsonString($investigationJsonstring);
    $investigation->setRevisionStatus($data['revision_status']);
    $entity=$investigation->save();

    // log the creation of the entity.
    $this->logger->notice('Created new InvestigationBuilder entity with ID @id.', ['@id' => $returnValue]);
    return $entity;
  }

  public function duplicateInvestigation(array $data) {

    $investigation = InvestigationBuilder::create($data);

    $entityId = $investigation->save();
    $returnValue['entityId'] = $investigation->id();
    $data_jsonstring = json_decode($data['json_string'],true);
    $newjsonstring = [
      'entityId' =>$investigation->id(),
      'uuid'=>uniqid(),
      'investigationLabel' =>$investigation->label(),
      'steps'=>$data_jsonstring['steps']
    ];
    $investigationJsonstring = json_encode($newjsonstring);
    $investigation->setJsonString($investigationJsonstring);
    $investigation->setRevisionStatus($data['revision_status']);
    $entity=$investigation->save();

    // log the creation of the entity.
    $this->logger->notice('Duplicated InvestigationBuilder entity with new ID @id.', ['@id' => $returnValue]);
    return $entity;
  }

  public function updateInvestigation($investigationId, array $data)
  {
    $investigation = InvestigationBuilder::load($investigationId);

    if (!$investigation) {
      throw new NotFoundHttpException();
    }

    $investigation->setName($data['label']);
    $investigation->setRevisionStatus($data['revision_status']);
    $entity=$investigation->save();

    $this->logger->notice('The Investigation @id has been updated.', ['@id' => $investigationId]);

    return $entity;
  }

  public function deleteInvestigation($investigationId){

    $investigation = InvestigationBuilder::load($investigationId);
    if (!$investigation) {
      throw new NotFoundHttpException(sprintf('Investigation with ID %s was not found.', $investigationId));
    }

    $investigation->delete();
    $this->logger->notice('Deleted Investigation entity with ID @id.', ['@id' => $investigationId]);

  }

  public function getJsonString(): string {
    return $this->get('json_string')->value;
  }
  





}
