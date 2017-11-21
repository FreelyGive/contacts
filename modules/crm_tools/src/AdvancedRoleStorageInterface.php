<?php

namespace Drupal\crm_tools;

use Drupal\user\RoleStorageInterface;

/**
 * Defines an interface for role entity storage classes.
 */
interface AdvancedRoleStorageInterface extends RoleStorageInterface {

  /**
   * Finds all parents of a given role ID.
   *
   * @param int $id
   *   Role ID to retrieve parents for.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of roles which are the parents of the role $id.
   */
  public function loadParents($id);

  /**
   * Finds all roles and orders tree.
   *
   * @param int $parent
   *   The role ID under which to generate the tree. If 0, generate the tree
   *   for the all roles.
   * @param int $max_depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   * @param bool $keyed
   *   Whether to key returned array by role ids. Defaults to false.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects that are the children the parent role.
   */
  public function loadTree($parent = 0, $max_depth = NULL, $keyed = FALSE);

}
