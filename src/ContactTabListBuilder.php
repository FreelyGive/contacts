<?php

namespace Drupal\contacts;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Contact tab entities.
 */
class ContactTabListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Contact tab');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\contacts\Entity\ContactTabInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['path'] = $entity->getPath();
    return $row + parent::buildRow($entity);
  }

}
