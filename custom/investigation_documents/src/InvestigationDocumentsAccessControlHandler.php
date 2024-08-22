<?php

declare(strict_types=1);

namespace Drupal\investigation_documents;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the investigationdocuments entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class InvestigationDocumentsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view investigation_documents_investigationdocuments'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit investigation_documents_investigationdocuments'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete investigation_documents_investigationdocuments'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete investigation_documents_investigationdocuments revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view investigation_documents_investigationdocuments revision', 'view investigation_documents_investigationdocuments']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert investigation_documents_investigationdocuments revision', 'edit investigation_documents_investigationdocuments']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create investigation_documents_investigationdocuments', 'administer investigation_documents_investigationdocuments'], 'OR');
  }

}
