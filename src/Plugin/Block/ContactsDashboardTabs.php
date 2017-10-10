<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to view contact dashboard tabs.
 *
 * @Block(
 *   id = "tabs",
 *   category = @Translation("Contacts"),
 *   deriver = "Drupal\contacts\Plugin\Deriver\ContactsDashboardTabsDeriver",
 * )
 */
class ContactsDashboardTabs extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  protected $tabManager;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
   */
  protected $layoutManager;

  /**
   * Whether we are building tabs via AJAX.
   *
   * @var bool
   */
  protected $ajax;

  /**
   * The subpage machine name.
   *
   * @var string
   */
  protected $subpage;

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Construct the Contact Dsahboard Tabs block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager $layout_manager
   *   The layout plugin manager.
   *
   * @todo Switch to core layout manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContactsTabManager $tab_manager, LayoutPluginManager $layout_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tabManager = $tab_manager;
    $this->layoutManager = $layout_manager;
    $this->ajax = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('contacts.tab_manager'),
      $container->get('plugin.manager.layout_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /* @var $entity \Drupal\Core\Entity\EntityInterface */
    $build = [];
    $this->subpage = $this->getContextValue('subpage');
    $this->user = $this->getContextValue('user');

    $manage_mode = \Drupal::state()->get('manage_mode');

    $this->buildTabs($build);
    $this->buildContent($build, $manage_mode);

    return $build;
  }

  /**
   * Adds the tabs section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  public function buildTabs(array &$build) {
    // @TODO Permission check.

    // Build content array.
    $content = [
      '#theme' => 'contacts_dash_tabs',
      '#weight' => -1,
      '#tabs' => [],
      '#attached' => [
        'library' => ['contacts/tabs'],
      ],
    ];

    foreach ($this->tabManager->getTabs($this->user) as $tab) {
      $url_stub = $tab->getPath();
      $content['#tabs'][$url_stub] = [
        'text' => $tab->label(),
        'link' => Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
          'user' => $this->user->id(),
          'subpage' => $url_stub,
        ]),
      ];

      // Swap links for AJAX request links.
      if ($this->ajax) {
        $content['#tabs'][$url_stub]['link_attributes']['data-ajax-url'] = Url::fromRoute('contacts.ajax_subpage', [
          'user' => $this->user->id(),
          'subpage' => $url_stub,
        ])->toString();
        $content['#tabs'][$url_stub]['link_attributes']['class'][] = 'use-ajax';
        $content['#tabs'][$url_stub]['link_attributes']['data-ajax-progress'] = 'fullscreen';
      }

      // Add tab id to attributes.
      $content['#tabs'][$url_stub]['link_attributes']['data-contacts-tab-id'] = $tab->getOriginalId();
    }

    // Add active class to current tab.
    if (isset($content['#tabs'][$this->subpage])) {
      $content['#tabs'][$this->subpage]['attributes']['class'][] = 'is-active';
      $content['#tabs'][$this->subpage]['link_attributes']['class'][] = 'is-active';
    }

    $build['tabs'] = $content;
  }

  /**
   * Adds the content section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  public function buildContent(array &$build, $manage_mode = FALSE) {
    $build['#attached']['drupalSettings']['dragMode'] = $manage_mode;

    $build['content'] = [
      '#prefix' => '<div id="contacts-tabs-content" class="contacts-tabs-content flex-fill">',
      '#suffix' => '</div>',
      '#theme' => 'contacts_dash_tab_content',
      '#subpage' => $this->subpage,
      '#manage_mode' => $manage_mode,
      '#region_attributes' => ['class' => ['drag-area']],
      '#content' => [],
    ];
    
    $tab = $this->tabManager->getTabByPath($this->user, $this->subpage);
    if ($tab) {
      $layout = $tab->get('layout') ?: 'contacts_tab_content.stacked';
      $layoutInstance = $this->layoutManager->createInstance($layout, []);

      // Get available regions from tab.
      foreach (array_keys($layoutInstance->getPluginDefinition()['regions']) as $region) {
        $build['content']['#content'][$region] = [];
      }

      $blocks = $this->tabManager->getBlocks($tab, $this->user);
      foreach ($blocks as $key => $block) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        if ($manage_mode) {
          $block_content = [
            '#theme' => 'contacts_dnd_card',
            '#attributes' => [
              'class' => ['draggable', 'draggable-active', 'card'],
              'data-dnd-contacts-block-tab' => $tab->id(),
            ],
            '#id' => $block->getPluginId(),
            '#block' => $block,
            '#user' => $this->user->id(),
            '#subpage' => $this->subpage,
            '#mode' => 'manage',
          ];
        }
        else {
          // @todo Order blocks by weight.
          $block_content = [
            '#theme' => 'block',
            '#attributes' => [],
            '#configuration' => $block->getConfiguration(),
            '#plugin_id' => $block->getPluginId(),
            '#base_plugin_id' => $block->getBaseId(),
            '#derivative_plugin_id' => $block->getDerivativeId(),
            '#weight' => $block->getConfiguration()['weight'],
            'content' => $block->build(),
          ];
          $block_content['content']['#title'] = $block->label();
        }

        $build['content']['#content'][$block->getConfiguration()['region']][] = $block_content;
      }
    }
    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    if ($manage_mode) {
      $build['content']['#region_attributes']['class'][] = 'show';
    }

    $build['content']['#content']['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];
  }

}
