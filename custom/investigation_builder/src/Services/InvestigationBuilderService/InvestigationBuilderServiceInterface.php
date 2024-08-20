<?php

declare(strict_types=1);

namespace Drupal\investigation_builder\Services\InvestigationBuilderService;

use Drupal\investigation_builder\Entity\InvestigationBuilder;

/**
 * @todo Add interface description.
 */
interface InvestigationBuilderServiceInterface {

  /**
   * Loads an InvestigationBuilder entity.
   *
   * @return InvestigationBuilder|null
   *   The InvestigationBuilder entity, or NULL if not found.
   */
  public function getInvestigationList();

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
   * Duplicates a new InvestigationBuilder entity.
   *
   * @param array $data
   *   The data for the new entity.
   *
   * @return InvestigationBuilder
   *   The duplicated InvestigationBuilder entity.
   */
  public function duplicateInvestigation(array $data);


  /**
   * Duplicates a new InvestigationBuilder entity.
   *
   * @param array $data
   *   The data for the new entity.
   *
   * @param $investigationId
   *   The id of the existing entity.
   *
   * @return InvestigationBuilder
   *   The duplicated InvestigationBuilder entity.
   */

  public function updateInvestigation($investigationId, array $data);

  /**
   * Delete a existing InvestigationBuilder entity.
   *
   * @param $investigationId
   *   The id of the existing entity.
   */
  public function deleteInvestigation($investigationId);

    /**
   * Gets the JSON string.
   *
   * @return string
   *   The JSON string.
   */
  public function getJsonString(): string;



}
