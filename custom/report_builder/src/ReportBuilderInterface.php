<?php

declare(strict_types=1);

namespace Drupal\report_builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a report builder entity type.
 */
interface ReportBuilderInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
