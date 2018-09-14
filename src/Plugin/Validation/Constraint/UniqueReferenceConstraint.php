<?php

namespace Drupal\contacts\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity reference field has a unique value.
 *
 * @Constraint(
 *   id = "ContactsUniqueReference",
 *   label = @Translation("Unique entity reference field constraint", context = "Validation"),
 * )
 */
class UniqueReferenceConstraint extends Constraint {

  public $message = 'A @entity_type with @field_name %value already exists.';

  /**
   * Whether to check within a specific bundle.
   *
   * @var bool
   */
  public $bundle = FALSE;

}
