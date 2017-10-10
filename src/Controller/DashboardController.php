<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager;
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
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
   */
  protected $layoutManager;

  /**
   * Construct the dashboard controller.
   *
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block plugin manager.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager $layout_manager
   *   The layout plugin manager.
   *
   * @todo Switch to core layout manager.
   */
  public function __construct(ContactsTabManager $tab_manager, BlockManager $block_manager, LayoutPluginManager $layout_manager) {
    $this->tabManager = $tab_manager;
    $this->blockManager = $block_manager;
    $this->layoutManager = $layout_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contacts.tab_manager'),
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.layout_plugin')
    );
  }


  /**
   * Return the AJAX command for changing tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we want to view.
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
   * @param string|null $tab
   *   The user we are viewing.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxManageModeTab($user, $subpage, $tab = NULL) {
    if (is_null($tab)) {
      // Toggle manage mode.
      \Drupal::state()->set('manage_mode_tab', 'blocks');
    }
    else {
      \Drupal::state()->set('manage_mode_tab', $tab);
    }

    return $this->ajaxTab($user, $subpage);
  }

  /**
   * Return the AJAX command for changing tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we want to view.
   * @param string $block
   *   The user we are viewing.
   * @param string $mode
   *   The mode to render the block for.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxManageModeConfigureBlock($user, $subpage, $block, $mode = 'configure') {
    $tab = $this->tabManager->getTabByPath($user, $subpage);
    if ($tab) {
      $blocks = $this->tabManager->getBlocks($tab, $user);

      if (isset($blocks[$block])) {
        $key = $block;
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        $block = $blocks[$key];

        $block_content = [
          '#theme' => 'contacts_dnd_card',
          '#attributes' => [
            'class' => ['draggable', 'draggable-active', 'card'],
            'data-dnd-contacts-block-tab' => $tab->id(),
          ],
          '#id' => $block->getPluginId(),
          '#block' => $block,
          '#user' => $user->id(),
          '#subpage' => $subpage,
          '#mode' => $mode,
        ];
      }
      else {
        drupal_set_message($this->t('Page not found.'), 'warning');

      }
    }

    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    // Create AJAX Response object.
    $response = new AjaxResponse();

    if (!empty($block_content)) {
      $response->addCommand(new ReplaceCommand("div[data-dnd-contacts-block-id='{$key}'][data-dnd-block-mode!='meta']", $block_content));
    }

    // Return ajax response.
    return $response;

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
   *
   * @todo Combine this method with \Drupal\contacts\Plugin\Block\ContactsDashboardTabs::buildContent().
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
      '#subpage' => $subpage,
      '#manage_mode' => $manage_mode,
      '#content' => [],
    ];
    $content['#attached']['drupalSettings']['dragMode'] = $manage_mode;

    $tab = $this->tabManager->getTabByPath($user, $subpage);
    if ($tab) {
      $layout = $tab->get('layout') ?: 'contacts_tab_content.stacked';
      $layoutInstance = $this->layoutManager->createInstance($layout, []);

      // Get available regions from tab.
      foreach (array_keys($layoutInstance->getPluginDefinition()['regions']) as $region) {
        $content['#content'][$region] = [];
      }

      $blocks = $this->tabManager->getBlocks($tab, $user);
      foreach ($blocks as $key => $block) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        // For some reason this brings in the theme hooks required...
        $block->build();
        if ($manage_mode) {
          $block_content = [
            '#theme' => 'contacts_dnd_card',
            '#attributes' => [
              'class' => ['draggable', 'draggable-active', 'card'],
              'data-dnd-contacts-block-tab' => $tab->id(),
            ],
            '#id' => $block->getPluginId(),
            '#block' => $block,
            '#user' => $user->id(),
            '#subpage' => $subpage,
            '#mode' => 'manage',
          ];
        }
        else {
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

          $block_content['content']['#title'] = $block->label();
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
    // @todo why does this break for empty tabs?
    // Probably need to move sidebar out of theme.
    if (function_exists('contacts_theme_dashboard_manage_sidebar_content')) {
      $sidebar_content = contacts_theme_dashboard_manage_sidebar_content();
    }

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($subpage, $url->toString()));
    $response->addCommand(new SettingsCommand(['dragMode' => $manage_mode], TRUE));

    if (isset($sidebar_content)) {
      $response->addCommand(new HtmlCommand('#sidebar-manage-content', $sidebar_content['content']));
    }

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
          $block_config = $this->addBlock($tab, $block, $region_data['region'], $weight);
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
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to add the block to.
   * @param string $block_data
   *   The block data provided by post request.
   * @param string $region
   *   The region the block is being moved to.
   * @param int $weight
   *   The weight of the block in that region.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function addBlock(&$tab, $block_data, $region, $weight) {
    $entity_type = $block_data['entity_type'];
    $entity_bundle = $block_data['entity_bundle'];

    switch ($entity_type) {
      case 'view':
        list($view_id, $view_display) = explode(':', $entity_bundle);
        $plugin_id = "views_block:{$view_id}-{$view_display}";
        break;

      case 'user':
      case 'profile':
        $plugin_id = "contacts_entity:{$entity_type}-{$entity_bundle}";
        break;
    }

    if (!isset($plugin_id)) {
      return FALSE;
    }

    $default_config = [
      'region' => $region,
      'weight' => $weight,
    ];

    /* @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance($plugin_id, $default_config);
    $block_config = $block->getConfiguration();

    if (!empty($block_data['entity_relationship'])) {
      // Give everything the user.
      if ($entity_type !== 'user') {
        $block_config['context_mapping'] = ['user' => 'user'];

        $entity_relationship = $block_data['entity_relationship'];
        $relationships = $tab->getRelationships();

        // @todo relationship name maps.
        $block_config['context_mapping'] = ['entity' => $entity_relationship];

        if (empty($relationships[$entity_relationship])) {
          // @todo this needs abstracting.
          if ($entity_type == 'profile') {
            $key = "{$entity_type}_{$entity_bundle}";
            $relationships[$entity_relationship] = [
              'id' => "typed_data_entity_relationship:entity:user:{$key}",
              'name' => $key,
              'source' => 'user',
            ];
          }
          $tab->setRelationships($relationships);
        }
      }
      else {
        $block_config['context_mapping'] = ['entity' => 'user'];
      }
    }

    return $block_config;
  }

}
