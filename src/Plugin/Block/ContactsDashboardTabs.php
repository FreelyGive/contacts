<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContactsTabManager $tab_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tabManager = $tab_manager;
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
      $container->get('contacts.tab_manager')
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
  public function buildContent(array &$build) {
    $build['content'] = [
      '#prefix' => '<div id="contacts-tabs-content" class="contacts-tabs-content flex-fill">',
      '#suffix' => '</div>',
      '#type' => 'contact_tab_content',
      '#tab' => $this->tabManager->getTabByPath($this->user, $this->subpage),
      '#user' => $this->user,
      '#subpage' => $this->subpage,
      '#region_attributes' => [],
      '#content' => [
        'left' => [],
        'right' => [],
      ],
    ];

    $build['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];
  }

}
