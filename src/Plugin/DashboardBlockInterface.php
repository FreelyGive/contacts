<?php

namespace Drupal\contacts\Plugin;

/**
 * Defines an interface for blocks placed on the Contacts Dashboard.
 */
interface DashboardBlockInterface {

  /**
   * Returns the block label with an edit link.
   *
   * @return string
   *   The block label with edit link.
   */
  public function editLabel();

}
