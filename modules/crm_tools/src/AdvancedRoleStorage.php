<?php

namespace Drupal\crm_tools;

use Drupal\user\RoleStorage;

/**
 * Controller class for user roles.
 */
class AdvancedRoleStorage extends RoleStorage implements AdvancedRoleStorageInterface {

  /**
   * Array of loaded parents keyed by child role ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Array of all loaded role ancestry keyed by ancestor role ID.
   *
   * @var array
   */
  protected $parentsAll = [];

  /**
   * Array of child roles keyed by parent role ID.
   *
   * @var array
   */
  protected $children = [];

  /**
   * Array of all loaded role descendants keyed by descendant role ID.
   *
   * @var array
   */
  protected $childrenAll = [];

  /**
   * Array of role parents keyed by vocabulary ID and child role ID.
   *
   * @var array
   */
  protected $treeParents = [];

  /**
   * Array of role ancestors keyed by vocabulary ID and parent role ID.
   *
   * @var array
   */
  protected $treeChildren = [];

  /**
   * Array of roles in a tree keyed by vocabulary ID and role ID.
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
  public function loadParents($id) {
    if (!isset($this->parents[$id])) {
      /* @var \Drupal\user\RoleInterface[] $roles */
      $roles = $this->loadMultiple();
      $parents = [];

      foreach ($roles as $role) {
        if ($role->id() == $id) {
          $parent_id = $role->getThirdPartySetting('crm_tools', 'crm_tools_parent');
          $parent = $this->load($parent_id);
          if ($parent) {
            $parents[$parent_id] = $parent;
          }
        }
      }

      $this->parents[$id] = $parents;
    }
    return $this->parents[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($id) {
    if (!isset($this->parentsAll[$id])) {
      $parents = [];
      if ($role = $this->load($id)) {
        $parents[$role->id()] = $role;
        $roles_to_search[] = $role->id();

        while ($id = array_shift($roles_to_search)) {
          if ($new_parents = $this->loadParents($id)) {
            foreach ($new_parents as $new_parent) {
              if (!isset($parents[$new_parent->id()])) {
                $parents[$new_parent->id()] = $new_parent;
                $roles_to_search[] = $new_parent->id();
              }
            }
          }
        }
      }

      $this->parentsAll[$id] = $parents;
    }
    return $this->parentsAll[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($id) {
    if (!isset($this->children[$id])) {
      /* @var \Drupal\user\RoleInterface[] $roles */
      $roles = $this->loadMultiple();
      $children = [];

      foreach ($roles as $role) {
        if ($role->getThirdPartySetting('crm_tools', 'crm_tools_parent') == $id) {
          $children[$role->id()] = $role;
        }
      }

      $this->children[$id] = $children;
    }
    return $this->children[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllChildren($id) {
    if (!isset($this->childrenAll[$id])) {
      $children = [];
      if ($role = $this->load($id)) {
        $children[$role->id()] = $role;
        $roles_to_search[] = $role->id();

        while ($id = array_shift($roles_to_search)) {
          if ($new_children = $this->loadChildren($id)) {
            foreach ($new_children as $new_child) {
              if (!isset($children[$new_child->id()])) {
                $children[$new_child->id()] = $new_child;
                $roles_to_search[] = $new_child->id();
              }
            }
          }
        }
      }

      $this->childrenAll[$id] = $children;
    }
    return $this->childrenAll[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($parent = 0, $max_depth = NULL, $keyed = FALSE) {
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {
      // Load full entities.
      /* @var \Drupal\user\RoleInterface[] $roles */
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

      // Loops over the parent roles and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents deroleines the current depth.
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

            if ($keyed) {
              $tree[$role->id()] = $role;
            }
            else {
              $tree[] = $role;
            }

            if (!empty($this->treeChildren[$id])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current role as parent for the next iteration.
              $process_parents[] = $id;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$id]);
              // Move pointer so that we get the correct role the next time.
              next($this->treeChildren[$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$parent]));

          if (!$has_children) {
            // We processed all roles in this hierarchy-level, reset pointer
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
