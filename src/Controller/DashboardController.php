<?php

namespace Drupal\contacts\Controller;

use Drupal\contacts\Ajax\ContactsTab;
use Drupal\contacts\ContactsTabManager;
use Drupal\contacts\Entity\ContactTab;
use Drupal\contacts\Form\DashboardBlockConfigureForm;
use Drupal\contacts\Form\DashboardTabConfigureForm;
use Drupal\contacts\Plugin\DashboardBlockInterface;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for contact dashboard tabs and ajax.
 */
class DashboardController extends ControllerBase {

  use AjaxHelperTrait;
  use StringTranslationTrait;

  /**
   * The tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  protected $tabManager;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The layout manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Construct the dashboard controller.
   *
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_manager
   *   The layout manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ContactsTabManager $tab_manager, BlockManager $block_manager, StateInterface $state, RequestStack $request_stack, PathValidatorInterface $path_validator, ContextHandlerInterface $context_handler, FormBuilderInterface $form_builder, LayoutPluginManager $layout_manager, RendererInterface $renderer) {
    $this->tabManager = $tab_manager;
    $this->blockManager = $block_manager;
    $this->state = $state;
    $this->request = $request_stack->getCurrentRequest();
    $this->pathValidator = $path_validator;
    $this->contextHandler = $context_handler;
    $this->formBuilder = $form_builder;
    $this->layoutManager = $layout_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contacts.tab_manager'),
      $container->get('plugin.manager.block'),
      $container->get('state'),
      $container->get('request_stack'),
      $container->get('path.validator'),
      $container->get('context.handler'),
      $container->get('form_builder'),
      $container->get('plugin.manager.core.layout'),
      $container->get('renderer')
    );
  }

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
   *
   * @todo Combine this method with \Drupal\contacts\Plugin\Block\ContactsDashboardTabs::buildContent().
   */
  public function ajaxTab(UserInterface $user, $subpage) {
    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    // Return as non-ajax.
    if (!$this->isAjax()) {
      return $this->redirect('page_manager.page_view_contacts_dashboard_contact', [
        'user' => $user->id(),
        'subpage' => $subpage,
      ]);
    }

    $block = $this->blockManager->createInstance('tabs:contacts_dashboard');

    // Build our contexts.
    if ($block instanceof ContextAwarePluginInterface) {
      $contexts = [
        'user' => new Context(new ContextDefinition('entity:user'), $user),
        'subpage' => new Context(new ContextDefinition('string'), $subpage),
      ];

      // Apply the contexts to the block.
      $this->contextHandler->applyContextMapping($block, $contexts);
    }

    $block_content = $block->build();
    $content['tabs'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $block->getConfiguration(),
      '#plugin_id' => $block->getPluginId(),
      '#base_plugin_id' => $block->getBaseId(),
      '#derivative_plugin_id' => $block->getDerivativeId(),
      '#weight' => $block->getConfiguration()['weight'],
      'content' => $block_content,
    ];

    // Prepend the content with system messages.
    $content['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    // Load the tab.
    $tab = $this->tabManager->getTabByPath($subpage);

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($tab->id(), $url->toString()));
    $response->addCommand(new HtmlCommand('#contacts-tabs', $content));

    $this->offCanvasCommand($response, $tab);

    // Return ajax response.
    return $response;
  }

  /**
   * Creates a new ajax response for page update triggered by off canvas.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab being updated.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function offCanvasUpdate(ContactTab $tab) {
    $response = new AjaxResponse();
    $this->offCanvasCommand($response, $tab);
    return $response;
  }

  /**
   * Adds AJAX command to response to show default tab offcanvas content.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   Response to add command to.
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to get content for.
   */
  public function offCanvasCommand(AjaxResponse $response, ContactTab $tab) {
    // Open/close the off-canvas tray.
    if ($this->state()->get('manage_mode')) {
      $main_content = $this->buildDefaultOffCanvas($tab);

      $content = $this->renderer->renderRoot($main_content);
      $main_content['#attached']['library'][] = 'core/drupal.dialog.off_canvas';
      $response->setAttachments($main_content['#attached']);

      // If the main content doesn't provide a title, use the title resolver.
      $title = isset($main_content['#title']) ? $main_content['#title'] : '';
      $response->addCommand(new OpenOffCanvasDialogCommand($title, $content, []));
    }
    else {
      $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    }
  }

  /**
   * Adds AJAX command to response to show default tab offcanvas content.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   Response to add command to.
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to get content for.
   */
  public function updateTabCommand(AjaxResponse $response, ContactTab $tab) {
    // Open/close the off-canvas tray.
    $response->addCommand(new InvokeCommand('[data-contacts-tab-id="' . $tab->id() . '"]', 'click'));
  }

  /**
   * Return the AJAX command for changing tab.
   *
   * @param bool|null $manage_mode
   *   The user we are viewing.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxManageMode($manage_mode = NULL) {
    if (is_null($manage_mode)) {
      // Toggle manage mode.
      $manage_mode = $this->state()->get('manage_mode');
    }

    // Get parameters from referrer request.
    $referrer = $this->request->server->get('HTTP_REFERER');
    $fake_request = Request::create($referrer);

    /* @var \Drupal\Core\Url $url_object */
    $url_object = $this->pathValidator->getUrlIfValid($fake_request->getRequestUri());

    if ($url_object) {
      $this->state()->set('manage_mode', !$manage_mode);

      $route_params = $url_object->getRouteParameters();
      /* @var \Drupal\user\UserInterface $contact */
      $contact = $this->entityTypeManager()->getStorage('user')->load($route_params['user']);
      return $this->ajaxTab($contact, $route_params['subpage']);
    }

    return FALSE;
  }

  /**
   * Adds AJAX command to response to show default tab offcanvas content.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to get content for.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxManageModeRefresh(ContactTab $tab) {
    $blocks = $this->tabManager->getBlocks($tab);

    $layout = $tab->get('layout') ?: 'contacts_tab_content.stacked';
    $layout = $this->layoutManager->createInstance($layout, []);

    $content['content'] = [
      '#prefix' => '<div id="contacts-tabs-content" class="contacts-tabs-content flex-fill">',
      '#suffix' => '</div>',
      '#type' => 'contact_tab_content',
      '#tab' => $tab,
      '#layout' => $layout,
      '#subpage' => $tab->getPath(),
      '#blocks' => $blocks,
      '#manage_mode' => TRUE,
      '#attributes' => ['class' => ['dash-content']],
    ];

    $content['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#contacts-tabs-content', $content));

    // Return ajax response.
    return $response;
  }

  /**
   * Provides a title callback to get the block's admin label.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The the tab entity that contains the block.
   * @param string $block_name
   *   The unique name of the block on the tab.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function offCanvasTitle(ContactTab $tab, $block_name) {
    $block_config = $tab->getBlock($block_name);
    $block = $this->blockManager->createInstance($block_config['id'], $block_config);

    return $this->t('Configure @block', [
      '@block' => $block->getPluginDefinition()['admin_label'],
    ]);
  }

  /**
   * Renders the off Canvas configure form for a new block.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The the tab entity that contains the block.
   * @param string $plugin_id
   *   The plugin id to create a new block from.
   * @param string|null $region
   *   The region to add the block to..
   *
   * @return array
   *   The renderable block config form.
   */
  public function offCanvasBlockAdd(ContactTab $tab, $plugin_id, $region = NULL) {
    $config = [];

    if ($region) {
      $config['region'] = $region;
    }

    /* @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance($plugin_id, $config);
    $this->tabManager->buildBlockContextMapping($tab, $block);

    $block_config = $block->getConfiguration();
    $block_config['create'] = TRUE;
    $name = $tab->addBlock($plugin_id, $block_config);

    return $this->offCanvasBlock($tab, $name);
  }

  /**
   * Renders the off Canvas configure form for a Dashboard block.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The the tab entity that contains the block.
   * @param string $block_name
   *   The unique name of the block on the tab.
   *
   * @return array
   *   The renderable block config form.
   */
  public function offCanvasBlock(ContactTab $tab, $block_name) {
    $block_config = $tab->getBlock($block_name);
    $block = $this->blockManager->createInstance($block_config['id'], $block_config);
    $content = [];

    $content['form '] = $this->formBuilder->getForm(DashboardBlockConfigureForm::class, $tab, $block);

    if ($block instanceof DashboardBlockInterface) {
      $content['meta'] = $block->getManageMeta();
    }
    return $content;
  }

  /**
   * Renders the off Canvas configure form for a Dashboard block.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The the tab entity that contains the block.
   *
   * @return array
   *   The renderable block config form.
   */
  public function offCanvasTab(ContactTab $tab) {
    $content = [];

    $content['form '] = $this->formBuilder->getForm(DashboardTabConfigureForm::class, $tab);

    $content['meta'] = $tab->getManageMeta();
    return $content;
  }

  /**
   * Add a block to a tab.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to add the block to.
   * @param string $block_config
   *   The block data provided by post request.
   *
   * @return mixed
   *   The block configuration array or FALSE if adding failed.
   */
  public function ajaxAddBlock(ContactTab $tab, $block_config) {
    if (empty($block_config['id'])) {
      // @todo Throw error - cannot create block without ID.
      return FALSE;
    }

    /* @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance($block_config['id'], $block_config);
    $block_config = $block->getConfiguration();

    // Give everything the tab's user context.
    if ($block_config['id'] !== 'contacts_entity:user-user') {
      $block_config['context_mapping'] = ['user' => 'user'];
      $relationships = $tab->getRelationships();

      // @todo Better relationship handling.
      if (!empty($block_config['_entity_relationship']) && !empty($relationships[$block_config['_entity_relationship']])) {
        $block_config['context_mapping'] = ['entity' => $block_config['_entity_relationship']];
        unset($block_config['_entity_relationship']);
      }
    }
    else {
      $block_config['context_mapping'] = ['entity' => 'user'];
    }

    $tab->setBlock($block_config['name'], $block_config);
    return $block_config;
  }

  /**
   * Update a block regions and positions in a tab.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function ajaxUpdateBlocks() {
    /* @var \Drupal\contacts\Entity\ContactTab $tab */
    $regions = \Drupal::request()->request->get('regions');
    $tab = \Drupal::request()->request->get('tab');
    $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);

    $changed = FALSE;
    foreach ($regions as $region_data) {
      foreach ($region_data['blocks'] as $weight => $block) {
        if (!isset($block['weight'])) {
          $block['weight'] = $weight;
        }

        if (!empty($region_data['region'])) {
          $block['region'] = $region_data['region'];
        }

        if (!empty($block['name'])) {
          $block_config = $tab->getBlock($block['name']);
          $block += $block_config;

          // Check for changes to block config.
          if (!empty(array_diff_assoc($block, $block_config))) {
            $changed = TRUE;
            $tab->setBlock($block['name'], $block);
          }
        }
        else {
          // Add a new block.
          if ($this->ajaxAddBlock($tab, $block)) {
            $changed = TRUE;
          }
        }
      }
    }

    // Return updated block configuration for verification.
    $response_data = ['blocks' => $tab->getBlocks()];

    // Save if anything has changed.
    if ($changed) {
      $tab->save();
      $response_data['tab_changed'] = TRUE;
    }

    $response = new Response();
    $response->setContent(json_encode($response_data));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);
    return $response;
  }

  /**
   * Update the order of tabs after sorting.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function ajaxUpdateTabs() {
    $tabs = \Drupal::request()->request->get('tabs');

    $json = [];
    $previous = -101;
    foreach (array_filter($tabs) as $tab) {
      /* @var \Drupal\contacts\Entity\ContactTab $tab */
      $tab = $this->entityTypeManager()->getStorage('contact_tab')->load($tab);
      $current_weight = $tab->get('weight');

      // Where possible preserve the existing tab weight.
      if ($previous < $current_weight) {
        $new = $current_weight;
      }
      else {
        $new = $previous + 5;
      }

      $tab->set('weight', $new);
      $tab->save();
      $json[] = ['id' => $tab->id(), 'weight' => $new];
      $previous = $new;
    }

    $response = new Response();
    $response->setContent(json_encode($json));
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(Response::HTTP_OK);

    return $response;
  }

  /**
   * The default off canvas information in manage mode.
   *
   * @param \Drupal\contacts\Entity\ContactTab $tab
   *   The tab to add the block to.
   *
   * @return array
   *   A render array.
   */
  public function buildDefaultOffCanvas(ContactTab $tab) {
    $output['#title'] = $this->t('Welcome to Manage Mode');

    $output['content'] = ['#type' => 'container'];
    $output['content']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('@label tab', ['@label' => $tab->label()]),
    ];

    $link_options = [
      'attributes' => ['target' => '_blank'],
      'query' => ['destination' => $this->getUrlGenerator()->generateFromRoute('<current>')],
    ];
    $output['content']['links'] = [
      '#theme' => 'item_list',
      '#items' => [
        'add_tab' => [
          '#type' => 'link',
          '#title' => t('Add Tab'),
          '#weight' => 15,
          '#url' => Url::fromRoute("entity.contact_tab.add_form", [], $link_options),
        ],
        'add_profile' => [
          '#type' => 'link',
          '#title' => t('Add Profile Type'),
          '#weight' => 20,
          '#url' => Url::fromRoute("entity.profile_type.add_form", [], $link_options),
        ],
        'manage_profiles' => [
          '#type' => 'link',
          '#title' => t('Manage Profile Types'),
          '#weight' => 25,
          '#url' => Url::fromRoute("entity.profile_type.collection", [], $link_options),
        ],
        'add_role' => [
          '#type' => 'link',
          '#title' => t('Add Contact Role'),
          '#weight' => 30,
          '#url' => Url::fromRoute("user.role_add", [], $link_options),
        ],
        'manage_roles' => [
          '#type' => 'link',
          '#title' => t('Manage Roles'),
          '#weight' => 35,
          '#url' => Url::fromRoute("entity.user_role.collection", [], $link_options),
        ],
      ],
      '#title' => 'Create:',
    ];

    return $output;
  }

}
