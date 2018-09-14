<?php

namespace Drupal\contacts_group\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\group\GroupMembership;

/**
 * Computed group membership field list.
 */
class GroupMembershipItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    $this->list = [];

    // Check whether we can get a memberships for this user.
    /* @var \Drupal\user\UserInterface $entity */
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('group_content');
    $group_contents = $storage->loadByProperties([
      'type' => $this->getSetting('group_type') . '-group_membership',
      'entity_id' => $entity->id(),
    ]);

    // Build our list.
    $delta = 0;
    foreach ($group_contents as $content) {
      $this->list[$delta] = $this->createItem($delta, $content);
      $this->list[$delta]->membership = new GroupMembership($content);
      $delta++;
    }
  }

}
