<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\Context;

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
        $block_manager = \Drupal::service('plugin.manager.block');
        // @todo Load configuration from somewhere.
        $config = [
          'label_display' => 'visible',
          'label' => 'Individual Summary',
          'view_mode' => 'default',
        ];
        /* @var $plugin_block \Drupal\Core\Block\BlockBase */
        $plugin_block = $block_manager->createInstance('entity_view:profile', $config);
        if ($user->hasRole('crm_indiv') && !empty($user->profile_crm_indiv->entity)) {
          $profile = $user->profile_crm_indiv->entity;
          $profile_context = new Context(new ContextDefinition('entity:profile', $this->t('Indiv Profile')), $profile);
          $plugin_block->setContext('entity', $profile_context);
        }
        elseif ($user->hasRole('crm_org') && !empty($user->profile_crm_org->entity)) {
          $profile = $user->profile_crm_org->entity;
          $profile_context = new Context(new ContextDefinition('entity:profile', $this->t('Org Profile')), $profile);
          $plugin_block->setContext('entity', $profile_context);
        }
        else {
          // No profile found.
          $content = ['#markup' => $this->t('Page not found')];
          break;
        }

        $block = $this->buildBlockRenderArray($plugin_block);

        $content = [
          '#theme' => 'contacts_summary',
          '#content' => [
            'left' => $block,
            'right' => '<div><h2>Summary Operations</h2><p>This block contains a list of useful operations to perform on the contact.</p></div>',
          ],
          '#attached' => [
            'library' => ['contacts/contacts-dashboard'],
          ],
        ];
        break;

      case 'notes':
        $block_manager = \Drupal::service('plugin.manager.block');
        // @todo Load configuration from somewhere.
        $config = [
          'label_display' => 'visible',
          'label' => 'Contact Notes',
        ];
        /* @var $plugin_block \Drupal\Core\Block\BlockBase */
        $plugin_block = $block_manager->createInstance('contacts_entity_form', $config);
        $operation = new Context(new ContextDefinition('string', $this->t('Operation')), 'crm_dashboard');
        $plugin_block->setContext('operation', $operation);

        if (!empty($user->profile_crm_notes->entity)) {
          $profile = $user->profile_crm_notes->entity;
          $profile_context = new Context(new ContextDefinition('entity:profile', $this->t('Notes Profile')), $profile);
          $plugin_block->setContext('entity', $profile_context);
        }
        else {
          // No profile found.
          $content = ['#markup' => $this->t('Page not found')];
          break;
        }
        $block = $this->buildBlockRenderArray($plugin_block);

        $content = [
          '#theme' => 'contacts_notes',
          '#content' => [
            'middle' => $block,
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

  /**
   * Build a render array from a block entity.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   Block entity being rendered.
   *
   * @return array
   *   Render array for the block.
   */
  private function buildBlockRenderArray(BlockPluginInterface $block) {
    if ($block->access(\Drupal::currentUser())) {
      $block_render_array = [
        '#theme' => 'block',
        '#attributes' => [],
        '#contextual_links' => [],
        '#weight' => 0,
        '#configuration' => $block->getConfiguration(),
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
      ];

      // Build the block and bubble its attributes up if possible. This
      // allows modules like Quickedit to function.
      // See \Drupal\block\BlockViewBuilder::preRender() for reference.
      $content = $block->build();
      if ($content !== NULL && !Element::isEmpty($content)) {
        foreach (['#attributes', '#contextual_links'] as $property) {
          if (isset($content[$property])) {
            $block_render_array[$property] += $content[$property];
            unset($content[$property]);
          }
        }
      }

      // If the block is empty, instead of trying to render the block
      // correctly return just #cache, so that the render system knows the
      // reasons (cache contexts & tags) why this block is empty.
      if (Element::isEmpty($content)) {
        $block_render_array = [];
        $cacheable_metadata = CacheableMetadata::createFromObject($block_render_array);
        $cacheable_metadata->applyTo($block_render_array);
        if (isset($content['#cache'])) {
          $block_render_array += $content['#cache'];
        }
      }

      $block_render_array['content'] = $content;
      return $block_render_array;
    }
  }

}
