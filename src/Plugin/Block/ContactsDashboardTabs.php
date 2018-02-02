<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountProxy;

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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The layout manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_manager
   *   The layout manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContactsTabManager $tab_manager, StateInterface $state, AccountProxy $current_user, LayoutPluginManager $layout_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tabManager = $tab_manager;
    $this->state = $state;
    $this->currentUser = $current_user;
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
      $container->get('state'),
      $container->get('current_user'),
      $container->get('plugin.manager.core.layout')
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

    $this->buildTabs($build);
    $this->buildContent($build);

    $build['#prefix'] = '<div id="contacts-tabs" class="contacts-tabs">';
    $build['#suffix'] = '</div>';

    return $build;
  }

  /**
   * Adds the tabs section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  protected function buildTabs(array &$build) {
    $manage_mode = $this->state->get('manage_mode');
    $verify = !$manage_mode ? $this->user : NULL;

    // Get verified tabs (if verification is required).
    $tabs = $this->tabManager->getTabs($verify);
    $tab_data = [];
    foreach ($tabs as $tab) {
      $tab_data[$tab->getOriginalId()] = [
        'label' => $tab->label(),
        'path' => $tab->getPath(),
      ];
    }

    // @TODO Permission check.
    $build['tabs'] = [
      '#type' => 'contact_tabs',
      '#tabs' => $tab_data,
      '#ajax' => $this->ajax,
      '#user' => $this->user,
      '#subpage' => $this->subpage,
      '#manage_mode' => $manage_mode,
      '#attributes' => ['class' => ['dash-content']],
    ];
  }

  /**
   * Adds the content section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  protected function buildContent(array &$build) {
    $tab = $this->tabManager->getTabByPath($this->subpage);
    $manage_mode = $this->state->get('manage_mode');

    // Verify tab if necessary.
    $verify = !$manage_mode ? $this->user : NULL;
    if ($verify) {
      $this->tabManager->verifyTab($tab, $verify);
    }

    $blocks = $this->tabManager->getBlocks($tab, $verify);

    $layout = $tab->get('layout') ?: 'contacts_tab_content.stacked';
    $layout = $this->layoutManager->createInstance($layout, []);

    $build['content'] = [
      '#prefix' => '<div id="contacts-tabs-content" class="contacts-tabs-content flex-fill">',
      '#suffix' => '</div>',
      '#type' => 'contact_tab_content',
      '#tab' => $tab,
      '#layout' => $layout,
      '#regions' => $regions,
      '#user' => $this->user,
      '#subpage' => $this->subpage,
      '#blocks' => $blocks,
      '#manage_mode' => $manage_mode,
      '#attributes' => ['class' => ['dash-content']],
    ];

    $build['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];
  }

}
