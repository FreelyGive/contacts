<?php

namespace Drupal\contacts_group\Plugin\Validation\Constraint;

use Drupal\group\Plugin\Validation\Constraint\GroupContentCardinalityValidator as GroupContraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * {@inheritdoc}
 */
class GroupContentCardinalityValidator extends GroupContraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($group_content, Constraint $constraint) {
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    // Exit early if the group is unsaved.
    if ($group = $group_content->getGroup()) {
      if ($group->isNew()) {
        return;
      }
    }

    parent::validate($group_content, $constraint);
  }

}
