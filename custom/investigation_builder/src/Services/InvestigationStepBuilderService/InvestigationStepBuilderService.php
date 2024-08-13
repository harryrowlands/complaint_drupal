<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationStepBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @todo Add class description.
 */
final class InvestigationStepBuilderService implements InvestigationStepBuilderServiceInterface {

  /**
   * Constructs an InvestigationStepBuilderService object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
 public function addInvestigationStep($investigationId, array $newStepData){
   $investigation = InvestigationBuilder::load($investigationId);

   if (!$investigation) {
     throw new NotFoundHttpException();
   }

   $investigationJsonString = $investigation->getJsonString();
   $investigationData = json_decode($investigationJsonString, true);
   $investigationData['steps'][] = $newStepData;
   $updatedJsonString = json_encode($investigationData);
   $investigation->setJsonString($updatedJsonString);
   $entity=$investigation->save();

   return $entity;
 }

 public function updateInvestigationStep($investigationId, $stepUuid, $stepData){
   $investigation = InvestigationBuilder::load($investigationId);

   if (!$investigation) {
     throw new NotFoundHttpException();
   }
   $investigationJsonString = $investigation->getJsonString();
   $investigationData = json_decode($investigationJsonString, true);

   foreach ($investigationData['steps'] as &$step) {
     if ($step['stepUuid'] == $stepUuid) {
       $step = $stepData;
       break;
     }
   }
   $updatedJsonString = json_encode($investigationData);
   $investigation->setJsonString($updatedJsonString);
   $entity = $investigation ->save();

   return $entity;
 }
 public function updateInvestigationStepOrder($investigationId, $stepsData){
   $investigation = InvestigationBuilder::load($investigationId);

   if (!$investigation) {
     throw new NotFoundHttpException();
   }

   $investigationJsonString = $investigation->getJsonString();
   $investigationData = json_decode($investigationJsonString, true);
   $investigationData['steps'] = $stepsData;
   $updatedJsonString = json_encode($investigationData);
   $investigation->setJsonString($updatedJsonString);
   $entity=$investigation->save();

   return $entity;
 }
 public function deleteInvestigationStep($investigationId, $stepUuid){

   $investigation = InvestigationBuilder::load($investigationId);

   if (!$investigation) {
     throw new NotFoundHttpException();
   }

   $investigationJsonString = $investigation->getJsonString();
   $investigationData = json_decode($investigationJsonString, true);
   $investigationData['steps'] = array_filter($investigationData['steps'], function($step) use($stepUuid){
     return $step['stepUuid'] !== $stepUuid;
   });
   $investigationData['steps'] = array_values($investigationData['steps']);
   $index = 0;
   foreach ($investigationData['steps'] as &$step) {
     $index++;
     $step['id'] = $index;
   }
   $updatedJsonString = json_encode($investigationData);
   $investigation->setJsonString($updatedJsonString);
   $entity = $investigation ->save();


 }

}
