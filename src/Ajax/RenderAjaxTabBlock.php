<?php

/**
 * @file
 * Contains \Drupal\contacts\Ajax\RenderAjaxTabBlock.
 */

namespace Drupal\contacts\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class RenderAjaxTabBlock implements CommandInterface {

  protected $content;
  protected $active;

  public function __construct($content, $uid, $active = 'summary') {
    global $base_url;

    $this->content = $content;
    $this->active = $active;
    $this->url = $base_url . '/admin/contacts/' . $uid . '/' . $active;
  }

  public function render() {
    return [
      'command' => 'renderAjaxTabBlock',
      'content' => $this->content,
      'active' => $this->active,
      'url' => $this->url,
    ];
  }
}
