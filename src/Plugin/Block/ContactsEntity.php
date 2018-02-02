<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\Plugin\DashboardBlockInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a block the user entity and any entity implementing ownership.
 *
 * Entity classes need to implement the EntityOwnerInterface and define the
 * contacts_entity property in their definition.
 *
 * @Block(
 *   id = "contacts_entity",
 *   category = @Translation("Contacts"),
 *   deriver = "Drupal\contacts\Plugin\Deriver\ContactsEntityBlockDeriver",
 *   dashboard_block = TRUE,
 * )
 */
class ContactsEntity extends BlockBase implements ContainerFactoryPluginInterface, DashboardBlockInterface {

  const MODE_VIEW = 'view';
  const MODE_VIEW_NEW = 'view_new';
  const MODE_FORM = 'form';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Constructs a ContactEntity block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder
   *   The entity form builder.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $form_builder, RequestStack $request_stack, AccountProxy $current_user, BlockManager $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->request = $request_stack->getCurrentRequest();
    $this->currentUser = $current_user;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => self::MODE_VIEW,
      'create' => NULL,
      'operation' => 'crm_dashboard',
      'view_mode' => 'crm_dashboard',
      'edit_link' => $this->pluginDefinition['_has_forms'] ? self::EDIT_LINK_CONTENT : FALSE,
      'edit_id' => 'edit',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $entity = $this->getContextValue('entity');
    if (!$entity) {
      $entity = $this->createEntity();
      if (!$entity) {
        return AccessResult::forbidden();
      }
    }
    $op = $this->getMode($entity);
    return $entity->access($op, NULL, TRUE);
  }

  /**
   * Whether we should use edit links.
   *
   * @return bool
   *   Whether we should use edit links.
   */
  protected function useEditLink() {
    return $this->pluginDefinition['_has_forms'] && $this->configuration['edit_link'] && !empty($this->configuration['edit_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditLink($mode) {
    // Check that we support and want edit links.
    if (!$this->useEditLink()) {
      return FALSE;
    }

    // Check we should show an edit link in this place.
    if ($this->configuration['edit_link'] != $mode) {
      return FALSE;
    }

    // If we are already in edit mode, don't show a link.
    if ($this->request->query->has('edit')) {
      return FALSE;
    }

    $params = [
      'user' => $this->getContextValue('user')->id(),
      'subpage' => $this->getContextValue('subpage'),
    ];

    if (empty($params['user']) || empty($params['subpage'])) {
      return FALSE;
    }

    $query = ['edit' => $this->configuration['edit_id']];
    $link = Link::createFromRoute('Edit', 'page_manager.page_view_contacts_dashboard_contact', $params, [
      'query' => $query,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-ajax-progress' => 'fullscreen',
        'data-ajax-url' => Url::fromRoute('contacts.ajax_subpage', $params, [
          'query' => $query,
        ])->toString(),
      ],
    ]);

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function processManageMode(array &$variables) {
    $definition = $this->blockManager->getDefinition($variables['id']);

    $variables['entity'] = $definition['_entity_type_id'];
    $variables['bundle'] = $definition['_bundle_id'];
    $variables['attributes']['data-contacts-manage-entity-type'] = $variables['entity'];
    $variables['attributes']['data-contacts-manage-entity-bundle'] = $variables['bundle'];

    $variables['footer']['links'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $this->getManageLinks(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getContextValue('entity');

    // If we don't have an entity, attempt creation.
    if (!$entity) {
      $entity = $this->createEntity();

      // If we still have no entity, return an empty render array.
      if (!$entity) {
        return [];
      }
    }

    if ($this->getMode($entity) == 'edit') {
      return $this->buildForm($entity);
    }
    return $this->buildView($entity);
  }

  /**
   * Get the mode to show for the entity.
   *
   * @param null|\Drupal\Core\Entity\EntityInterface $entity
   *   The entity. If not provided, we will get it from the context.
   *
   * @return string
   *   The mode to use, either 'view' or 'edit'.
   */
  protected function getMode(EntityInterface $entity = NULL) {
    $definition = $this->getPluginDefinition();
    $config = $this->getConfiguration();
    if (!$entity) {
      $entity = $this->getContextValue('entity');
    }

    // If there are no forms, always show a view.
    if (!$definition['_has_forms']) {
      return 'view';
    }

    // Show a form if we are form only.
    if ($config['mode'] == self::MODE_FORM) {
      return 'edit';
    }

    // If we have requested this to be editable, show the form.
    if ($this->useEditLink() && $this->request->query->get('edit') == $this->configuration['edit_id']) {
      return 'edit';
    }

    // View if the entity is not new or we want to view new entities.
    if (!$entity || !$entity->isNew() || $config['mode'] == self::MODE_VIEW_NEW) {
      return 'view';
    }

    return 'edit';
  }

  /**
   * Create an entity, if the definition and config allow it.
   *
   * @return false|\Drupal\Core\Entity\EntityInterface
   *   The created entity or FALSE if the definition or config do not allow it.
   */
  protected function createEntity() {
    // Check our definition allows creation.
    $definition = $this->getPluginDefinition();
    if (!$definition['_allow_create']) {
      return FALSE;
    }

    // Check our config allows creation.
    $config = $this->getConfiguration();
    if (!$config['create']) {
      return FALSE;
    }

    // Check create access.
    $bundle = $definition['_bundle_key'] ? $definition['_bundle_id'] : NULL;
    $context = [];
    if (is_a($this->entityTypeManager->getDefinition($definition['_entity_type_id'])->getClass(), EntityOwnerInterface::class, TRUE)) {
      $user = $this->getContextValue('user');
      $context['owner'] = $user;
    }
    if (!$this->entityTypeManager->getAccessControlHandler($definition['_entity_type_id'])->createAccess($bundle, NULL, $context)) {
      return FALSE;
    }

    // Build our values.
    $values = [];

    // If this entity type has bundles, set the appropriate key.
    if ($bundle) {
      $values[$definition['_bundle_key']] = $bundle;
    }

    // Create the entity.
    $entity = $this->entityTypeManager->getStorage($definition['_entity_type_id'])->create($values);

    // If this has an owner, set it.
    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwner($this->getContextValue('user'));
    }

    return $entity;
  }

  /**
   * Build the view mode render array for the block.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   A render array containing the rendered entity.
   */
  protected function buildView(EntityInterface $entity) {
    $build = [];

    // Output an edit link if relevant.
    if ($link = $this->getEditLink(self::EDIT_LINK_CONTENT)) {
      $build['edit'] = $link->toRenderable();
    }

    // Get the view builder.
    $definition = $this->getPluginDefinition();
    $config = $this->getConfiguration();
    $view_builder = $this->entityTypeManager->getViewBuilder($definition['_entity_type_id']);
    $build['view'] = $view_builder->view($entity, $config['view_mode']);

    return $build;
  }

  /**
   * Build the form mode render array for the block.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   A render array containing the form.
   */
  protected function buildForm(EntityInterface $entity) {
    // Manually build our action and redirect.
    $route_name = 'page_manager.page_view_contacts_dashboard_contact';
    $route_params = [
      'user' => $this->getContextValue('user')->id(),
      'subpage' => $this->getContextValue('subpage'),
    ];

    $options = ['query' => $this->request->query->all()];
    // @see \Drupal\Core\Form\FormBuilder::buildFormAction.
    unset($options['query'][FormBuilder::AJAX_FORM_REQUEST], $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);

    // Build our URLs.
    $action = Url::fromRoute($route_name, $route_params, $options);
    unset($options['query']['edit']);
    $redirect = Url::fromRoute($route_name, $route_params, $options);

    // Get the form.
    $config = $this->getConfiguration();

    // Fall back to the default form if the requested one doesn't exist.
    if ($this->entityTypeManager->getDefinition($this->pluginDefinition['_entity_type_id'], TRUE)->getFormClass($config['operation'])) {
      $operation = $config['operation'];
    }
    else {
      $operation = 'default';
    }

    $form = $this->formBuilder->getForm($entity, $operation, [
      'redirect' => $redirect,
    ]);
    $form['#action'] = $action->toString();

    return ['form' => $form];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $definition = $this->getPluginDefinition();

    $entity_type = $this->entityTypeManager->getDefinition($this->pluginDefinition['_entity_type_id']);

    // Add the module which defines the entity type.
    $dependencies['module'][] = $entity_type->getProvider();

    // If we have a bundle for creating, add it's config dependencies.
    if ($definition['_bundle_key']) {
      $dependency = $entity_type->getBundleConfigDependency($definition['_bundle_id']);
      $dependencies[$dependency['type']][] = $dependency['name'];
    }

    return $dependencies;
  }

  /**
   * Get list of links to display on manage block.
   *
   * @return array
   *   Array of links to be based to an 'item_list' render array.
   */
  protected function getManageLinks() {
    $entity_id = $this->pluginDefinition['_entity_type_id'];
    $bundle_id = $this->pluginDefinition['_bundle_id'];
    $entity_definition = $this->entityTypeManager->getDefinition($entity_id);
    $bundle_type = $entity_definition->getBundleEntityType();
    $operations = [];
    // Add manage fields and display links if this entity type is the bundle
    // of another and that type has field UI enabled.
    if ($bundle_type && $entity_definition->get('field_ui_base_route')) {
      $account = $this->currentUser;
      if ($account->hasPermission('administer ' . $entity_id . ' fields')) {
        $operations['manage-fields'] = [
          '#type' => 'link',
          '#title' => t('Manage fields'),
          '#weight' => 15,
          '#url' => Url::fromRoute("entity.{$entity_id}.field_ui_fields", [
            $bundle_type => $bundle_id,
          ]),
        ];
      }
      if ($account->hasPermission('administer ' . $entity_id . ' form display')) {
        $operations['manage-form-display'] = [
          '#type' => 'link',
          '#title' => t('Manage form display'),
          '#weight' => 20,
          '#url' => Url::fromRoute("entity.entity_form_display.{$entity_id}.default", [
            $bundle_type => $bundle_id,
          ]),
        ];
      }
      if ($account->hasPermission('administer ' . $entity_id . ' display')) {
        $operations['manage-display'] = [
          '#type' => 'link',
          '#title' => t('Manage display'),
          '#weight' => 25,
          '#url' => Url::fromRoute("entity.entity_view_display.{$entity_id}.default", [
            $bundle_type => $bundle_id,
          ]),
        ];
      }
    }

    return $operations;
  }

}
