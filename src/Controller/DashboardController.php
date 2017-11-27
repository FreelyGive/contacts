<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\contacts\Entity\ContactTab;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Block\BlockManager;
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
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Construct the dashboard controller.
   *
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block plugin manager.
   */
  public function __construct(ContactsTabManager $tab_manager, BlockManager $block_manager) {
    $this->tabManager = $tab_manager;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contacts.tab_manager'),
      $container->get('plugin.manager.block')
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
   *
   * @todo Combine this method with \Drupal\contacts\Plugin\Block\ContactsDashboardTabs::buildContent().
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
   * Add a block to a tab.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to add the block to.
   * @param string $block_config
   *   The block data provided by post request.
   *
   * @return mixed
   *   The block configuration array or FALSE if adding failed.
   */
  public function addBlock(ContactTab &$tab, $block_config) {
    if (empty($block_config['id'])) {
      // @todo Throw error - cannot create block without ID.
      return FALSE;
    }

    /* @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance($block_config['id'], $block_config);
    $block_config = $block->getConfiguration();

    // Give everything the tab's user context.
    if ($block_config['id'] !== 'contacts_entity:user-user') {
      $block_config['context_mapping'] = ['user' => 'user'];
      $relationships = $tab->getRelationships();

      // @todo Better relationship handling.
      if (!empty($block_config['_entity_relationship']) && !empty($relationships[$block_config['_entity_relationship']])) {
        $block_config['context_mapping'] = ['entity' => $block_config['_entity_relationship']];
        unset($block_config['_entity_relationship']);
      }
    }
    else {
      $block_config['context_mapping'] = ['entity' => 'user'];
    }

    $tab->setBlock($block_config['name'], $block_config);
    return $block_config;
  }

  /**
   * Update a block regions and positions in a tab.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function updateBlocks() {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $regions = \Drupal::request()->request->get('regions');
    $tab = \Drupal::request()->request->get('tab');
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $changed = FALSE;
    foreach ($regions as $region_data) {
      foreach ($region_data['blocks'] as $weight => $block) {
        if (!isset($block['weight'])) {
          $block['weight'] = $weight;
        }

        if (!empty($region_data['region'])) {
          $block['region'] = $region_data['region'];
        }

        if (!empty($block['name'])) {
          $block_config = $tab->getBlock($block['name']);
          $block += $block_config;

          // Check for changes to block config.
          if (!empty(array_diff_assoc($block, $block_config))) {
            $changed = TRUE;
            $tab->setBlock($block['name'], $block);
          }
        }
        else {
          // Add a new block.
          if ($this->addBlock($tab, $block)) {
            $changed = TRUE;
          }
        }
      }
    }

    // Return updated block configuration for verification.
    $response_data = ['blocks' => $tab->getBlocks()];

    // Save if anything has changed.
    if ($changed) {
      $tab->save();
      $response_data['tab_changed'] = TRUE;
    }

    $response = new Response();
    $response->setContent(json_encode($response_data));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);
    return $response;
  }

}
