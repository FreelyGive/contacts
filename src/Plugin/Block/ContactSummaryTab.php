<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\block\BlockInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockManager;

/**
 * Block for the contact summary tab.
 *
 * @Block(
 *   id = "contact_summary_tab",
 *   admin_label = @Translation("Contact summary tab"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("Contact"))
 *   }
 * )
 */
class ContactSummaryTab extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Block\BlockManager definition.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Construct the Contact Summary Tab block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManager $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'contacts_summary',
      '#content' => [],
      '#attached' => [
        'library' => ['contacts/contact'],
      ],
    ];

    $contact = $this->getContextValue('user');

    // Main profile on the left.
    $label = NULL;
    if ($contact->hasRole('crm_indiv')) {
      $label = 'Person Summary';
      $profile = $contact->profile_crm_indiv->entity;
    }
    elseif ($contact->hasRole('crm_org')) {
      $label = 'Organisation Summary';
      $profile = $contact->profile_crm_org->entity;
    }
    if (!empty($label)) {
      /* @var \Drupal\contacts\Plugin\Block\ContactsEntity $block */
      $block = $this->blockManager->createInstance('contacts_entity:profile', [
        'mode' => ContactsEntity::MODE_VIEW_NEW,
        'edit_link' => ContactsEntity::EDIT_LINK_TITLE,
        'edit_id' => 'main',
        'label' => $label,
        'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
      ]);
      $block->setContextValue('user', $contact);
      $block->setContextValue('entity', $profile);
      $build['#content']['left'] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block->getConfiguration(),
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
        '#block' => $block,
        'content' => $block->build(),
      ];
      $build['#content']['left']['content']['#title'] = $block->label();
    }

    // Notes on the right.
    /* @var \Drupal\contacts\Plugin\Block\ContactsEntity $block */
    $block = $this->blockManager->createInstance('contacts_entity:profile', [
      'mode' => ContactsEntity::MODE_VIEW_NEW,
      'edit_link' => ContactsEntity::EDIT_LINK_TITLE,
      'edit_id' => 'notes',
      'label' => $this->t('Notes'),
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
    ]);
    $block->setContextValue('user', $contact);
    $block->setContextValue('entity', $contact->profile_crm_notes->entity);
    $build['#content']['right'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $block->getConfiguration(),
      '#plugin_id' => $block->getPluginId(),
      '#base_plugin_id' => $block->getBaseId(),
      '#derivative_plugin_id' => $block->getDerivativeId(),
      '#block' => $block,
      'content' => $block->build(),
    ];
    $build['#content']['right']['content']['#title'] = $block->label();

    return $build;
  }

}
