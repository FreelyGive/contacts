<?php

/**
 * @file
 * Post update functions for Delay Repay Payments.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;



/**
 * Adds new completed date field to payment entity types.
 */
function contacts_post_update_update_crm_indiv_field() {
  $values = [
    'langcode' => 'en',
    'status' => TRUE,
    'dependencies' => [
      'module' => [
        'name',
        'profile',
      ],
    ],
    'id' => 'profile.crm_name',
    'field_name' => 'crm_name',
    'entity_type' => 'profile',
    'type' => 'name',
    'settings' => [
      'components' => [
        'title' => FALSE,
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ],
      'minimum_components' => [
        'title' => FALSE,
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ],
      'labels' => [
        'title' => 'Title',
        'given' => 'Given',
        'middle' => 'Middle name(s)',
        'family' => 'Family',
        'generational' => 'Generational',
        'credentials' => 'Credentials',
      ],
      'max_length' => [
        'title' => 31,
        'given' => 63,
        'middle' => 127,
        'family' => 63,
        'generational' => 15,
        'credentials' => 225,
      ],
      'autocomplete_source' => [
        'title' => ['title' => 'title'],
        'given' => [],
        'middle' => [],
        'family' => [],
        'generational' => '0',
        'credentials' => [],
      ],
      'autocomplete_separator' => [
        'title' => ' ',
        'given' => ' -',
        'middle' => ' -',
        'family' => ' -',
        'generational' => ' ',
        'credentials' => ', ',
      ],
      'allow_family_or_given' => FALSE,
      'title_options' => [
        '-- --',
        'Mr.',
        'Mrs.',
        'Miss.',
        'Ms.',
        'Dr.',
        'Prof.',
      ],
      'generational_options' => [
        '-- --',
        'Jr.',
        'Sr.',
        'I',
        'II',
        'III',
      ],
      'sort_options' => [
        'title' => FALSE,
        'generational' => FALSE,
      ],
    ],
    'module' => 'name',
    'locked' => FALSE,
    'cardinality' => 1,
    'translatable' => TRUE,
    'indexes' => [],
    'persist_with_no_fields' => FALSE,
    'custom_storage' => FALSE,
  ];
  $field_storage = FieldStorageConfig::create($values);
  $field_storage->save();

  $values = [
    'langcode' => 'en',
    'status' => TRUE,
    'dependencies' => [
      'config' => [
        'field.storage.profile.crm_name',
        'profile.type.crm_indiv',
      ],
      'module' => ['name'],
    ],
    'id' => 'profile.crm_indiv.crm_name',
    'field_name' => 'crm_name',
    'entity_type' => 'profile',
    'bundle' => 'crm_indiv',
    'label' => 'Name',
    'description' => '',
    'required' => FALSE,
    'translatable' => FALSE,
    'default_value' => [],
    'default_value_callback' => '',
    'settings' => [
      'size' => [
        'title' =>  6,
        'given' => 20,
        'middle' => 20,
        'family' => 20,
        'generational' => 5,
        'credentials' => 35,
      ],
      'title_display' => [
        'title' => 'description',
        'given' => 'description',
        'middle' => 'description',
        'family' => 'description',
        'generational' => 'description',
        'credentials' => 'description',
      ],
      'field_type' => [
        'title' => 'select',
        'given' => 'text',
        'middle' => 'text',
        'family' => 'text',
        'generational' => 'select',
        'credentials' => 'text',
      ],
      'inline_css' => [
        'title' => '',
        'given' => '',
        'middle' => '',
        'family' => '',
        'generational' => '',
        'credentials' => '',
      ],
      'component_css' => '',
      'component_layout' => 'default',
      'show_component_required_marker' => FALSE,
      'credentials_inline' => FALSE,
      'override_format' => 'default',
    ],
    'field_type' => 'name',
  ];
  $field = FieldConfig::create($values);
  $field->save();
}

