<?php

namespace Drupal\contacts_group\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'group_membership' field type.
 *
 * @FieldType(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Field item for computed group membership fields"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\contacts_group\Plugin\Field\GroupMembershipItemList",
 * )
 */
class GroupMembershipItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['membership'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Membership'))
      ->setDescription(new TranslatableMarkup('The membership wrapper.'));

    return $properties;
  }

}
