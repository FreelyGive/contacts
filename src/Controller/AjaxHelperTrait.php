<?php

namespace Drupal\contacts\Controller;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Provides a helper to determine if the current request is via AJAX.
 *
 * @todo Replace with core version once merged https://www.drupal.org/node/2896535.
 */
trait AjaxHelperTrait {

  /**
   * Determines if the current request is via AJAX.
   *
   * @return bool
   *   TRUE if the current request is via AJAX, FALSE otherwise.
   */
  protected function isAjax() {
    return in_array($this->getRequestWrapperFormat(), [
      'drupal_ajax',
      'drupal_dialog',
      'drupal_dialog.off_canvas',
      'drupal_modal',
    ]);
  }

  /**
   * Gets the wrapper format of the current request.
   *
   * @string
   *   The wrapper format.
   */
  protected function getRequestWrapperFormat() {
    return \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  }

}
