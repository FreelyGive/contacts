<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for contact dashboard tabs and ajax.
 */
class DashboardController extends ControllerBase {

  /**
   * The tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  protected $tabManager;

  /**
   * Construct the dashboard controller.
   *
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager.
   */
  public function __construct(ContactsTabManager $tab_manager) {
    $this->tabManager = $tab_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contacts.tab_manager')
    );
  }


  /**
   * Return the AJAX command for changing tab.
   *
   * @param boolean|null $manage_mode
   *   The user we are viewing.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxManageMode($user, $subpage, $manage_mode = NULL) {
    if (is_null($manage_mode)) {
      // Toggle manage mode.
      $manage_mode = \Drupal::state()->get('manage_mode');
    }

    \Drupal::state()->set('manage_mode', !$manage_mode);

    return $this->ajaxTab($user, $subpage);
  }

  /**
   * Return the AJAX command for changing tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we want to view.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxTab(UserInterface $user, $subpage) {
    $manage_mode = \Drupal::state()->get('manage_mode');

    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    $content = [
      '#theme' => 'contacts_dash_tab_content',
      '#region_attributes' => ['class' => ['drag-area']],
      '#content' => [
        'left' => [],
        'right' => [],
      ],
    ];
    $content['#attached']['drupalSettings']['dragMode'] = $manage_mode;

    $tab = $this->tabManager->getTabByPath($user, $subpage);
    if ($tab && $blocks = $this->tabManager->getBlocks($tab, $user)) {
      foreach ($blocks as $key => $block) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        // @todo fix weight.
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

        $block_content['#attributes']['data-dnd-contacts-block-tab'] = $tab->getOriginalId();
        $block_content['#attributes']['data-dnd-contacts-block-id'] = $key;
        $block_content['content']['#title'] = $block->label();

        if ($manage_mode) {
          $block_content['#attributes']['class'][] = 'ui-sortable-handle';
          $block_content['#attributes']['class'][] = 'draggable-active';
          $block_content['#attributes']['class'][] = 'card';
          $block_content['content']['#title'] = '';

          $block_content['content']['header'] = [
            '#type' => 'form',
            '#attributes' => ['class' => ['form-inline', 'card-header']],
            'label' => [
              '#type' => 'textfield',
              '#default_value' => 'test',
              '#disabled' => TRUE,
            ],
            'edit_link' => [
              '#type' => 'html_tag',
              '#tag' => 'a',
              '#value' => '',
              '#attributes' => [
                'href' => '#',
                'class' => ['ml-auto', 'align-self-center', 'card-link', 'edit-draggable'],
              ],
            ],
            'delete_link' => [
              '#type' => 'html_tag',
              '#tag' => 'a',
              '#value' => '',
              '#attributes' => [
                'href' => '#',
                'class' => ['card-link', 'delete-draggable'],
              ],
            ],
          ];

          $view = $block_content['content']['view'];
          unset($block_content['content']['view']);
          $block_content['content']['view'] = $view;
        }

        $content['#content'][$block->getConfiguration()['region']][] = $block_content;
      }
    }
    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    if ($manage_mode) {
      $content['#region_attributes']['class'][] = 'show';
    }

    // Prepend the content with system messages.
    $content['#content']['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    // Also update the manage sidebar content.
    $sidebar_content = contacts_theme_dashboard_manage_sidebar_content();

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($subpage, $url->toString()));
    $response->addCommand(new SettingsCommand(['dragMode' => $manage_mode], TRUE));
    $response->addCommand(new HtmlCommand('.sidebar-manage-content', $sidebar_content));
    $response->addCommand(new HtmlCommand('#contacts-tabs-content', $content));

    // Return ajax response.
    return $response;
  }

  /**
   * Update a block position in a tab.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function updateBlocks() {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $blocks = \Drupal::request()->request->get('block_data');
    $tab = \Drupal::request()->request->get('tab');
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    foreach ($blocks as $region_data) {
      foreach ($region_data['blocks'] as $weight => $block) {
        $changed = FALSE;
        $block_config = $tab->getBlock($block['id']);

        if (!$block_config) {
          $block_config = $this->addBlock($tab->getOriginalId(), $block, $region_data['region'], $weight);
          $changed = TRUE;
        }

        if ($block_config['region'] != $region_data['region']) {
          $changed = TRUE;
          $block_config['region'] = $region_data['region'];
        }

        if ($block_config['weight'] != $weight) {
          $changed = TRUE;
          $block_config['weight'] = $weight;
        }

        if ($changed) {
          $tab->setBlock($block['id'], $block_config);
          $tab->save();
        }
      }
    }

    $json = $tab->getBlocks();
    $response = new Response();
    $response->setContent(json_encode($json));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);

    return $response;
  }

  /**
   * Add a block to a tab.
   *
   * @param string $tab
   *   The id of the tab being updated.
   * @param string $block
   *   The id of the block being moved.
   * @param string $region
   *   The region the block is being moved to.
   * @param int $weight
   *   The weight of the block in that region.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function addBlock($tab, $block, $region, $weight) {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $profile_type = $block['profile_type'];
    $profile_relationship = $block['profile_relationship'];
    $type = $this->entityTypeManager()->getStorage('profile_type')->load($profile_type);
    $block_config = [
      'id' => 'contacts_entity:profile',
      'label' => $type->label(),
      'label_display' => 'visible',
      'mode' => 'view',
      'create' => $profile_type,
      'edit_link' => 'title',
      'region' => $region,
      'weight' => $weight,
      'context_mapping' => ['entity' => $profile_relationship],
    ];

    $relationships = $tab->getRelationships();

    if (empty($relationships[$profile_relationship])) {
      // @todo add new relationship.
    }

    return $block_config;
  }

  /**
   * Remove a block from a tab.
   *
   * @param string $tab
   *   The id of the tab being updated.
   * @param string $block
   *   The id of the block being moved.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function removeBlock($tab, $block) {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $blocks = $tab->getBlocks();

    if ($changed = isset($blocks[$block])) {
      unset($blocks[$block]);
      $tab->setBlocks($blocks);
      $tab->save();
    }

    $json = $tab->getBlocks();
    $json['#updated'] = $changed;

    $response = new Response();
    $response->setContent(json_encode($json));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);

    return $response;
  }

  /**
   * Update a block position in a tab.
   *
   * @param string $tab
   *   The id of the tab being updated.
   * @param string $block
   *   The id of the block being updated.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function updateBlockTitle($tab, $block) {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $block_config = $tab->getBlock($block);
    $changed = FALSE;

    // Get label from post data.
    $label = \Drupal::request()->request->get('label');
    if ($label && $block_config['label'] != $label) {
      $changed = TRUE;
      $block_config['label'] = $label;
    }

    if ($changed) {
      $tab->setBlock($block, $block_config);
      $tab->save();
    }

    $json = $tab->getBlocks();
    $json['#updated'] = $changed;
    $json['#label'] = $label;

    $response = new Response();
    $response->setContent(json_encode($json));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);

    return $response;
  }

}
