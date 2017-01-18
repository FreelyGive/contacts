<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Controller routines for contact dashboard tabs and ajax.
 */
class DashboardController extends ControllerBase {

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
    $content = $this->renderBlock($user, $subpage);
    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($content, $subpage, $url->toString()));

    // Return ajax response.
    return $response;
  }

  /**
   * Render the block content.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we are viewing.
   *
   * @return array
   *   A render array.
   */
  public function renderBlock(UserInterface $user, $subpage) {
    switch ($subpage) {
      case 'summary':
        $content = [
          '#theme' => 'contacts_summary',
          '#content' => [
            'left' => '<div><h2>Contact Summary</h2><p>This block contains a summary of information about the contact.</p></div>',
            'right' => '<div><h2>Summary Operations</h2><p>This block contains a list of useful operations to perform on the contact.</p></div>',
          ],
          '#attached' => [
            'library' => ['contacts/contacts-dashboard'],
          ],
        ];
        break;

      case 'indiv':
        $content = [
          '#theme' => 'contacts_indiv',
          '#content' => [
            'middle' => '<div><h2>Individual Information</h2><p>This block contains information about the individual, such as date of birth and gender.</p></div>',
          ],
          '#attached' => [
            'library' => ['contacts/contacts-dashboard'],
          ],
        ];
        break;

      case 'notes':
        $content = [
          '#theme' => 'contacts_notes',
          '#content' => [
            'middle' => '<div><h2>Contact Notes</h2><p>This block contains notes made about the contact by staff members.</p></div>',
          ],
          '#attached' => [
            'library' => ['contacts/contacts-dashboard'],
          ],
        ];
        break;

      default:
        $content = ['#markup' => $this->t('Page not found')];
    }

    return $content;
  }

}
