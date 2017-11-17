<?php

namespace Drupal\crm_tools;

use Drupal\user\RoleInterface;
use Drupal\user\RoleStorage;

/**
 * Controller class for user roles.
 */
class AdvancedRoleStorage extends RoleStorage implements AdvancedRoleStorageInterface {

  /**
   * Array of loaded parents keyed by child term ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Array of term parents keyed by vocabulary ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = [];

  /**
   * Array of term ancestors keyed by vocabulary ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = [];

  /**
   * Array of terms in a tree keyed by vocabulary ID and term ID.
   *
   * @var array
   */
  protected $treeRoles = [];

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = [];

  /**
   * {@inheritdoc}
   */
  public function isPermissionInRoles($permission, array $rids) {
    // @todo Check parent roles.
    return parent::isPermissionInRoles($permission, $rids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($id) {
    if (!isset($this->parents[$id])) {
      /* @var RoleInterface[] $roles */
      $roles = $this->loadMultiple();
      $parents = [];

      foreach ($roles as $role) {
        if ($role->getThirdPartySetting('crm_tools', 'crm_tools_parent') == $id) {
          $parents[$role->id()] = $role;
        }
      }

      $this->parents[$id] = $parents;
    }
    return $this->parents[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($parent = 0, $max_depth = NULL) {
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {
      // Load full entities.
      /* @var RoleInterface[] $roles */
      $roles = $this->loadMultiple();
      // We cache trees, so it's not CPU-intensive to call on a role and its
      // children, too.
      if (empty($this->treeChildren)) {
        $this->treeChildren = [];
        $this->treeParents = [];
        $this->treeRoles = [];

        foreach ($roles as $role) {
          $role->set('parent', $role->getThirdPartySetting('crm_tools', 'crm_tools_parent', 0));
          $this->treeChildren[$role->get('parent')][] = $role->id();
          $this->treeParents[$role->id()][] = $role->get('parent');
          $this->treeRoles[$role->id()] = $role;
        }
      }


      $max_depth = (!isset($max_depth)) ? count($this->treeChildren) : $max_depth;
      $tree = [];

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = [];
      $process_parents[] = $parent;

      // Loops over the parent terms and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents determines the current depth.
        $depth = count($process_parents);
        if ($max_depth > $depth && !empty($this->treeChildren[$parent])) {
          $has_children = FALSE;
          $child = current($this->treeChildren[$parent]);


          do {
            if (empty($child)) {
              break;
            }
            $role = $roles[$child];
            $role->set('depth', $depth);
            unset($role->parent);
            $id = $role->id();
            $role->set('parents', $this->treeParents[$id]);
            $tree[] = $role;
            if (!empty($this->treeChildren[$id])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current term as parent for the next iteration.
              $process_parents[] = $id;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$id]);
              // Move pointer so that we get the correct term the next time.
              next($this->treeChildren[$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$parent]));

          if (!$has_children) {
            // We processed all terms in this hierarchy-level, reset pointer
            // so that this function works the next time it gets called.
            reset($this->treeChildren[$parent]);
          }
        }
      }
      $this->trees[$cache_key] = $tree;
    }

    return $this->trees[$cache_key];
  }
  
}
