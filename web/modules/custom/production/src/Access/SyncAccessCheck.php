<?php
namespace Drupal\production\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for syncing to prod.
 */
class SyncAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function appliesTo() {
    return '_production_access_check';
  }
  
  public function access(AccountInterface $account) {
    if ($account->hasPermission('sync to prod') && _is_site('uat')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
