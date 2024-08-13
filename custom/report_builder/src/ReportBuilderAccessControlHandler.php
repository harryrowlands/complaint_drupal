<?php

declare(strict_types=1);

namespace Drupal\report_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the report builder entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ReportBuilderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view report_builder'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit report_builder'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete report_builder'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete report_builder revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view report_builder revision', 'view report_builder']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert report_builder revision', 'edit report_builder']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create report_builder', 'administer report_builder'], 'OR');
  }

}
