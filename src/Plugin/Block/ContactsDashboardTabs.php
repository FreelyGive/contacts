<?php

/**
 * @file
 * Contains \Drupal\contacts\Plugin\Block\ContactsDashboardTabs.
 */

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\Controller\DashboardController;
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
   * The block controller.
   *
   * @var \Drupal\contacts\Controller\DashboardController.
   */
  protected $blockController;

  /**
   * The block machine name.
   *
   * @var string.
   */
  protected $subpage;

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User.
   */
  protected $user;

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User.
   */
  protected $ajax;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockController = new DashboardController();
    $this->ajax = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
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
  public function buildTabs(&$build) {
    global $base_path;

    // @TODO Permission check.

    // Build content array.
    $content = [
      '#theme' => 'contacts_dash_tabs',
      '#weight' => 1,
      '#tabs' => [],
      '#attached' => [
        'library' => ['contacts/contacts-ajax-tabs'],
      ]
    ];

    // @TODO load tabs rather than hard code.
    $tabs = [
      'summary' => 'Summary',
      'notes' => 'Notes'
    ];

    foreach ($tabs as $machine => $label) {
      $content['#tabs'][$machine] = [
        'text' => $label,
        'link' => Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
          'user' => $this->user->id(),
          'subpage' => $machine,
        ]),
      ];

      // Swap links for AJAX request links.
      if ($this->ajax) {
        $content['#tabs'][$machine]['link_attributes']['data-ajax-url'] = Url::fromRoute('contacts.ajax_subpage', [
          'user' => $this->user->id(),
          'subpage' => $machine,
        ])->toString();
        $content['#tabs'][$machine]['link_attributes']['class'][] = 'use-ajax';
      }
    }

    // Add subpage class to current tab.
    $content['#tabs'][$this->subpage]['attributes']['class'][] = 'is-active';
    $content['#tabs'][$this->subpage]['link_attributes']['class'][] = 'is-active';

    $build['tabs'] = $content;
  }

  /**
   * Adds the content section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  public function buildContent(&$build) {
    if (in_array($this->subpage, ['summary', 'indiv', 'notes'])) {
      $content = $this->blockController->renderBlock($this->user, $this->subpage);
      $build['content'] = $content + ['#weight' => 2];
    }
  }
}
