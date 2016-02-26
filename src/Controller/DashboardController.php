<?php

/**
 * @file
 * Contains \Drupal\contacts\Controller\DashboardController.
 */

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\RenderAjaxTabBlock;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for contact dashboard tabs and ajax.
 */
class DashboardController extends ControllerBase {

  /**
   * The contact user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The type of js request made.
   *
   * @var string
   *   Either 'nojs' or 'ajax' depending on the request.
   */
  protected $js;

  /**
   * The active tab.
   *
   * @var string
   *   The machine name of the active tab/page.
   */
  protected $active;


  /**
   * Redirect to the summary tab by default.
   *
   * @param \Drupal\user\Entity\User $user
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectSummary($user) {
    $route = 'page_manager.page_view_contacts_dashboard_contact';
    $parameters = [
      'user' => $user,
      'subpage' => 'summary'
    ];

    return new RedirectResponse(\Drupal::url($route, $parameters));
  }

  /**
   * Render the individual block content.
   *
   * @param \Drupal\user\Entity\User $user
   * @param string $js
   *
   * @return mixed
   *   Either a page render array or \Drupal\Core\Ajax\AjaxResponse.
   */
  public function renderIndividualBlock($user, $js = 'nojs') {
    if (is_numeric($user)) {
      $user = User::load($user);
    }

    $this->user = $user;
    $this->js = $js;
    $this->active = 'indiv';

    $content = [
      '#theme' => 'contacts_indiv',
      '#content' => [
        'middle' => '<div><h2>Individual Information</h2>
<p>This block contains information about the individual, such as date of birth and gender.</p>
</div>',
      ],
      '#attached' => [
        'library' => ['contacts/contacts-dashboard'],
      ],
    ];

    return $this->renderInPage($content);
  }

  /**
   * Render the summary block content.
   *
   * @param \Drupal\user\Entity\User $user
   * @param string $js
   *
   * @return mixed
   *   Either a page render array or \Drupal\Core\Ajax\AjaxResponse.
   */
  public function renderSummaryBlock($user, $js = 'nojs') {
    if (is_numeric($user)) {
      $user = User::load($user);
    }

    $this->user = $user;
    $this->js = $js;
    $this->active = 'summary';

    $content = [
      '#theme' => 'contacts_summary',
      '#content' => [
        'left' => '<div><h2>Contact Summary</h2>
<p>This block contains a summary of information about the contact.</p>
</div>',
        'right' => '<div><h2>Summary Operations</h2>
<p>This block contains a list of useful operations to perform on the contact.</p>
</div>',
      ],
      '#attached' => [
        'library' => ['contacts/contacts-dashboard'],
      ],
    ];

    return $this->renderInPage($content);
  }

  /**
   * Render the notes block content.
   *
   * @param \Drupal\user\Entity\User $user
   * @param string $js
   *
   * @return mixed
   *   Either a page render array or \Drupal\Core\Ajax\AjaxResponse.
   */
  public function renderNotesBlock($user, $js = 'nojs') {
    if (is_numeric($user)) {
      $user = User::load($user);
    }

    $this->user = $user;
    $this->js = $js;
    $this->active = 'notes';

    $content = [
      '#theme' => 'contacts_notes',
      '#content' => [
        'middle' => '<div><h2>Contact Notes</h2>
<p>This block contains notes made about the contact by staff members.</p>
</div>',
      ],
      '#attached' => [
        'library' => ['contacts/contacts-dashboard'],
      ],
    ];
    return $this->renderInPage($content);
  }

  /**
   * Return the block content via an AJAX request.
   *
   * @param $content
   *   Drupal render array.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  protected function renderAjax($content) {
    // Create AJAX Response object.
    $response = new AjaxResponse();

    $content = \Drupal::service('renderer')->render($content)->jsonSerialize();

    // Call the readMessage javascript function.
    $response->addCommand( new RenderAjaxTabBlock($content, $this->user->id(), $this->active));

    // Return ajax response.
    return $response;
  }

  /**
   * Decide whether to return the block in a render array or an AJAX response.
   *
   * @param $content
   *   Drupal render array.
   *
   * @return mixed
   *   Either a page render array or \Drupal\Core\Ajax\AjaxResponse.
   */
  protected function renderInPage($content) {
    if ($this->js == 'ajax') {
      return $this->renderAjax($content);
    }

    return $content;
  }

}
