<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;

/**
 * @todo Add interface description.
 */
interface InvestigationBuilderServiceInterface {

  /**
   * Creates a new InvestigationBuilder entity.
   *
   * @param array $data
   *   The data for the new entity.
   *
   * @return InvestigationBuilder
   *   The created InvestigationBuilder entity.
   */
  public function createInvestigation(array $data);

  /**
   * Loads an InvestigationBuilder entity.
   *
   * @return InvestigationBuilder|null
   *   The InvestigationBuilder entity, or NULL if not found.
   */
  public function loadInvestigationBuilderList();

}
