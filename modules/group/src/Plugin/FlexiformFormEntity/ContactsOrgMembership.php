<?php

namespace Drupal\contacts_group\Plugin\FlexiformFormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form entity plugin for Contacts Org Memberships.
 *
 * @FlexiformFormEntity(
 *   id = "contacts_org_membership",
 *   label = @Translation("Organisation membership"),
 *   entity_type = "group_content",
 *   bundle = "contacts_org-group_membership",
 *   context = {
 *     "member" = @ContextDefinition("entity:user", label = @Translation("Member")),
 *     "group" = @ContextDefinition("entity:user", label = @Translation("Organisation"))
 *   }
 * )
 */
class ContactsOrgMembership extends FlexiformFormEntityBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_content_storage
   *   The group content entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $group_content_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupContentStorage = $group_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('group_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    // Get hold of our member and group.
    /* @var \Drupal\user\UserInterface $member */
    $member = $this->getContextValue('member');
    /* @var \Drupal\user\UserInterface $group_user */
    $group_user = $this->getContextValue('group');
    /* @var \Drupal\group\Entity\GroupInterface $group */
    $group = $group_user ? $group_user->group->entity : NULL;

    if (!$member || !$group) {
      return NULL;
    }

    // See if there is already a membership.
    if (!$member->isNew() && !$group->isNew()) {
      $existing = $this->groupContentStorage->loadByProperties([
        'type' => $this->pluginDefinition['bundle'],
        'gid' => $group->id(),
        'entity_id' => $member->id(),
      ]);
      $content = reset($existing);
      if ($content) {
        return $content;
      }
    }

    // Create a new membership.
    return $this->groupContentStorage->create([
      'type' => $this->pluginDefinition['bundle'],
      'gid' => $group,
      'entity_id' => $member,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave(EntityInterface $entity) {
    /* @var \Drupal\group\Entity\GroupContentInterface $entity */
    // If the group user is new, we likely don't have the same object so we need
    // to manually update it form the form values.
    $group = $entity->getGroup();
    if ($group->isNew()) {
      /* @var \Drupal\user\UserInterface $group_user */
      $group_user = $this->getContextValue('group');

      // Force a refresh of the group values, as the profile save happens on a
      // different object and so we may have an out of date item.
      $group_user->group->resetValue();

      // Retrieve the refreshed group.
      /* @var \Drupal\group\Entity\GroupInterface $group */
      $group = $group_user->group->entity;
      $entity->gid->entity = $group;
    }

    parent::doSave($entity);
  }

}
