<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Block\BlockManager;
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
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   *
   * @todo Combine this method with \Drupal\contacts\Plugin\Block\ContactsDashboardTabs::buildContent().
   */
  public function ajaxTab(UserInterface $user, $subpage) {
    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    $content = [
      '#type' => 'container',
      'content' => [
        '#type' => 'contact_tab_content',
        '#attributes' => ['class' => ['dash-content']],
        '#subpage' => $subpage,
        '#user' => $user,
        '#tab' => $this->tabManager->getTabByPath($user, $subpage),
      ],
    ];

    // Prepend the content with system messages.
    $content['messages'] = [
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

}
