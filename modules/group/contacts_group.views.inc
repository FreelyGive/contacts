<?php

/**
 * @file
 * Views hook implementations for Contacts Group.
 */

/**
 * Implements hook_views_data().
 */
function contacts_group_views_data() {
  $entity_type_manager = \Drupal::entityTypeManager();

  // Core only handles base fields and config fields so we have to add it!
  $data['group__contacts_org']['table'] = [
    'entity_type' => 'group',
    'group' => $entity_type_manager->getDefinition('group')->getLabel(),
    'provider' => 'contacts',
    'join' => [
      'groups_field_data' => [
        'left_field' => 'id',
        'field' => 'entity_id',
        'extra' => [
          [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => TRUE,
          ],
        ],
      ],
    ],
  ];

  /* @var \Drupal\entity\BundleFieldDefinition $field */
  $field = \Drupal::service('entity_field.manager')->getFieldDefinitions('group', 'contacts_org')['contacts_org'];
  $user_label = $entity_type_manager->getDefinition('user')->getLabel();
  $data['group__contacts_org']['contacts_org_target_id'] = [
    'title' => $field->getLabel(),
    'help' => $field->getDescription(),
    'field' => ['id' => 'field'],
    'argument' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'entity type' => 'user',
      'label' => $user_label,
      'title' => $user_label,
      'id' => 'standard',
    ],
    'entity field' => 'contacts_org',
  ];

  return $data;
}
