<?php

namespace Drupal\contacts\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Update the contacts tab.
 *
 * @ingroup ajax
 */
class ContactsTab implements CommandInterface {

  /**
   * The new active tab.
   *
   * @var string
   */
  protected $tab;

  /**
   * The new URL we are be at.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a ContactsTab object.
   *
   * @param string $tab
   *   The new active tab.
   * @param string $url
   *   The new URL we are at.
   */
  public function __construct($tab, $url) {
    $this->tab = $tab;
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'contactsTab',
      'activeTab' => $this->tab,
      'url' => $this->url,
    ];
  }

}
