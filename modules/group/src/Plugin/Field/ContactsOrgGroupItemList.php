<?php

namespace Drupal\contacts_group\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed group field list.
 */
class ContactsOrgGroupItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    $this->list = [];

    // Check whether we can get a group for this user.
    /* @var \Drupal\user\UserInterface $entity */
    $entity = $this->getEntity();
    if (!$entity->hasRole('crm_org')) {
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('group');

    // See if a group already exists.
    if (!$entity->isNew()) {
      $group = $storage->loadByProperties([
        'type' => 'contacts_org',
        'contacts_org' => $entity->id(),
      ]);
      $group = reset($group);
    }

    // If not create it.
    if (empty($group)) {
      $group = $storage->create([
        'type' => 'contacts_org',
        'label' => $entity->label(),
        'contacts_org' => $entity,
      ]);
    }

    // Set the group against the list.
    $this->list[0] = $this->createItem(0, $group);
  }

  /**
   * Reset the computed value.
   */
  public function resetValue() {
    $this->valueComputed = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Don't automatically save referenced groups. If they need to be saved,
    // they will be referenced by group content which will handle the save.
    // Otherwise we get into recursion if the entity hasn't been saved and we
    // only need to group to be saved if it's referenced by group content.
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Ensure values are recomputed post save.
    $this->resetValue();

    // If this was an insert, we don't need to do anything else.
    if (!$update) {
      return;
    }

    /* @var \Drupal\user\UserInterface $entity */
    $entity = $this->getEntity();
    /* @var \Drupal\user\UserInterface $original */
    $original = $entity->original;

    // If the group already stored, check if we need to update the label.
    if (!$this->isEmpty()) {
      /* @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->list[0]->entity;
      if (!$group->isNew() && $group->label() != $entity->label()) {
        $group->set('label', $entity->label())->save();
      }
    }
    // If we have no group, but used to be a crm_org, check the database
    // directly to remove the groups.
    elseif ($original->hasRole('crm_org')) {
      $this->removeGroups($entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->removeGroups($this->getEntity()->id());
  }

  /**
   * Remove groups for a user.
   *
   * @param int $id
   *   The user ID to remove groups for.
   */
  protected function removeGroups($id) {
    $storage = \Drupal::entityTypeManager()->getStorage('group');
    $groups = $storage->loadByProperties([
      'type' => 'contacts_org',
      'contacts_org' => $id,
    ]);
    $storage->delete($groups);
  }

}
