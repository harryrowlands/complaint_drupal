<?php

declare(strict_types=1);

namespace Drupal\investigation_builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an investigation builder entity type.
 */
interface InvestigationBuilderInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
