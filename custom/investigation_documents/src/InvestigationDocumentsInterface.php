<?php

declare(strict_types=1);

namespace Drupal\investigation_documents;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an investigation documents entity type.
 */
interface InvestigationDocumentsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
