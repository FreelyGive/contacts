<?php

namespace Drupal\contacts\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity view block definitions for each entity type.
 */
class ContactsEntityBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new ContactsEntityBlockDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // We are only interested in the user entity itself and entities that can
      // have owners.
      $has_owner = class_implements($entity_type->getClass(), EntityOwnerInterface::class);
      if ($entity_type_id == 'user' || $has_owner) {
        $has_forms = $entity_type->hasFormClasses();

        // Basic definition.
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['admin_label'] = $this->t('Contacts entity form (@label)', ['@label' => $entity_type->getLabel()]);

        // The entity is required for the user or types that don't have forms.
        $this->derivatives[$entity_type_id]['context']['entity'] = new ContextDefinition('entity:' . $entity_type_id, $entity_type->getLabel(), $entity_type_id == 'user' || !$has_forms);
        // If this can have an owner, add the context so we can do create forms.
        if ($has_owner && $has_forms) {
          $this->derivatives[$entity_type_id]['context']['user'] = new ContextDefinition('entity:user', $this->t('User'), FALSE);
        }

        // Add a few other pieces of info.
        $this->derivatives[$entity_type_id]['_entity_type_id'] = $entity_type_id;
        $this->derivatives[$entity_type_id]['_has_forms'] = $has_forms;
        $this->derivatives[$entity_type_id]['_allow_create'] = $has_owner && $has_forms;
        $this->derivatives[$entity_type_id]['_bundle_key'] = $entity_type->getKey('bundle');
      }
    }
    return $this->derivatives;
  }

}
