<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we want to view.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxTab(UserInterface $user, $subpage) {
    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    $content = [
      '#theme' => 'contacts_dash_tab_content',
      '#content' => [
        'left' => [],
        'right' => [],
      ],
    ];

    $tab = $this->tabManager->getTabByPath($user, $subpage);
    if ($tab && $blocks = $this->tabManager->getBlocks($tab, $user)) {
      foreach ($blocks as $block) {
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
        $block_content['content']['#title'] = $block->label();
        $content['#content'][$block->getConfiguration()['region']][] = $block_content;
      }
    }
    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    // Prepend the content with system messages.
    $content['#content']['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($subpage, $url->toString()));
    $response->addCommand(new HtmlCommand('#contacts-tabs-content', $content));

    // Return ajax response.
    return $response;
  }

  /**
   * Return the AJAX command for changing tab.
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
   * @todo return a http response code.
   * @return array
   *   The response commands.
   */
  public function moveBlock($tab, $block, $region, $weight) {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $block_config = $tab->getBlock($block);
    $block_config['region'] = $region;
    $block_config['weight'] = $weight;

    $tab->setBlock($block, $block_config);

    $tab->save();

    return [];
  }

}
