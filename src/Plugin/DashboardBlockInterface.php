<?php

namespace Drupal\contacts\Plugin;

/**
 * Defines an interface for blocks placed on the Contacts Dashboard.
 */
interface DashboardBlockInterface {

  const EDIT_LINK_TITLE = 'title';
  const EDIT_LINK_CONTENT = 'content';

  /**
   * Get the edit link, if applicable.
   *
   * @param string $mode
   *
   *
   * @return \Drupal\Core\Link|false
   *   The edit link, or FALSE if there is none.
   */
  function getEditLink($mode);

}
