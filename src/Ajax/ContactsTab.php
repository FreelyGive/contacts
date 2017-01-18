<?php

namespace Drupal\contacts\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * Update the contacts tab.
 *
 * @ingroup ajax
 */
class ContactsTab implements CommandInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The content the tab.
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

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
   * @param string|array $content
   *   The content that will be inserted in the matched element(s), either a
   *   render array or an HTML string.
   * @param string $tab
   *   The new active tab.
   * @param string $url
   *   The new URL we are at.
   */
  public function __construct($content, $tab = NULL, $url = NULL) {
    $this->content = $content;
    $this->tab = $tab;
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'contactsTab',
      'data' => $this->getRenderedContent(),
      'activeTab' => $this->tab,
      'url' => $this->url,
    ];
  }

}
