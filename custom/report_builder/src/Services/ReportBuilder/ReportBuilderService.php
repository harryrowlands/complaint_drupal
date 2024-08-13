<?php

declare(strict_types=1);

namespace Drupal\report_builder\Services\ReportBuilder;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @todo Add class description.
 */
final class ReportBuilderService implements ReportBuilderServiceInterface {

  /**
   * Constructs a ReportBuilderService object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function doSomething(): void {
    // @todo Place your code here.
  }

  // generate a report entity from an investigation entity
  public function createReport(string $investigation_uid)
  {
    $investigation = InvestigationBuilder::load($investigation_uid);
    //$revisionService = new RevisionService();
    //$returnValue = $revisionService->getTabView($tabView);

    $report = ReportBuilder::create(array(
        'name' => $investigation->getName(),
        'json_string' => $investigation->getJsonString(),
        
      )
    );
  }
  
}
