<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Entity\ContactTab;

/**
 * Provides AJAX responses to rebuild the Layout Builder.
 */
trait DashboardRebuildTrait {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The dashboard controller.
   *
   * @var \Drupal\contacts\Controller\DashboardController
   */
  protected $dashboardContoller;

  /**
   * Rebuilds the layout.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The section storage.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  protected function rebuildAndReturn(ContactTab $tab) {
    $response = $this->rebuildDashboard($tab);
    $this->dashboardContoller->offCanvasCommand($response, $tab);
    $this->dashboardContoller->updateTabCommand($response, $tab);
    return $response;
  }

  /**
   * Rebuilds the layout.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The section storage.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  protected function rebuildDashboard(ContactTab $tab) {
    $this->dashboardContoller = $this->classResolver->getInstanceFromDefinition(DashboardController::class);
    return $this->dashboardContoller->ajaxManageModeRefresh($tab);
  }

}
