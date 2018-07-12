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
      // We are only interested in entity types that have been approved.
      if (!$entity_type->get('contacts_entity')) {
        continue;
      }

      // We are only interested in the user entity itself and entities that can
      // have owners.
      $has_owner = class_implements($entity_type->getClass(), EntityOwnerInterface::class);
      if ($entity_type_id == 'user' || $has_owner) {
        $has_forms = $entity_type->hasFormClasses();

        // Check if we should expand out entity bundles.
        $bundle_entity_type = $entity_type->getBundleEntityType();
        $use_bundles = !empty($entity_type->get('contacts_use_bundles')) && $bundle_entity_type;
        // Expand out a derivative per entity bundle.
        if ($use_bundles) {
          $bundle_types = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
          $bundles = array_keys($bundle_types);
        }
        else {
          $bundles = [$entity_type_id];
        }

        foreach ($bundles as $bundle) {
          $derivative_key = "{$entity_type_id}-{$bundle}";

          // Basic definition.
          $this->derivatives[$derivative_key] = $base_plugin_definition;
          $this->derivatives[$derivative_key]['admin_label'] = $this->t('@label block', ['@label' => $entity_type->getLabel()]);

          // If we are using bundles add note the bundle label.
          if ($use_bundles && !empty($bundle_types[$bundle])) {
            $this->derivatives[$derivative_key]['admin_label'] = $this->t('@bundle @type block', [
              '@type' => $entity_type->getLabel(),
              '@bundle' => $bundle_types[$bundle]->label(),
            ]);
          }

          // The entity is required for the user or types that don't have forms.
          $this->derivatives[$derivative_key]['context']['entity'] = new ContextDefinition('entity:' . $entity_type_id, $entity_type->getLabel(), $entity_type_id == 'user' || !$has_forms);
          // If this has an owner, add the context so we can do create forms.
          if ($has_owner && $has_forms) {
            $this->derivatives[$derivative_key]['context']['user'] = new ContextDefinition('entity:user', $this->t('User'), FALSE);
            $this->derivatives[$derivative_key]['context']['subpage'] = new ContextDefinition('string', $this->t('Subpage'), FALSE);
          }

          // Add a few other pieces of info.
          $this->derivatives[$derivative_key]['_entity_type_id'] = $entity_type_id;
          $this->derivatives[$derivative_key]['_has_forms'] = $has_forms;
          $this->derivatives[$derivative_key]['_allow_create'] = $has_owner && $has_forms;
          $this->derivatives[$derivative_key]['_bundle_key'] = $entity_type->getKey('bundle');
          $this->derivatives[$derivative_key]['_bundle_id'] = $bundle;

          // @todo Find better way to do access.
          if ($entity_type_id == 'profile') {
            /* @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
            $profile_type = $this->entityTypeManager->getStorage('profile_type')->load($bundle);
            $this->derivatives[$derivative_key]['_required_hats'] = array_filter($profile_type->getRoles());
            $this->derivatives[$derivative_key]['_tab_relationships']['user']['entity'] = "profile_{$bundle}";
          }
        }
      }
    }
    return $this->derivatives;
  }

}
