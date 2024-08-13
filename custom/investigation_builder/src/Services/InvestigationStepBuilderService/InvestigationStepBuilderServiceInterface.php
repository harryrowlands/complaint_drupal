<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationStepBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;

/**
 * @todo Add interface description.
 */
interface InvestigationStepBuilderServiceInterface {

  /**
   * @todo Add method description.
   */
  public function addInvestigationStep($investigationId, array $newStepData);

  public function updateInvestigationStep($investigationId, $stepId, array $stepData);

  public function updateInvestigationStepOrder($investigationId, array $stepsData);

  public function deleteInvestigationStep($investigationId, $stepId);

}
