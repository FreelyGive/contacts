<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a block the user entity and any entity implementing ownership.
 *
 * Entity classes need to implement the EntityOwnerInterface and block
 * annotation/definition should contain the dashboard_block property.
 *
 * @Block(
 *   id = "contacts_entity",
 *   category = @Translation("Contacts"),
 *   deriver = "Drupal\contacts\Plugin\Deriver\ContactsEntityBlockDeriver",
 *   dashboard_block = TRUE,
 * )
 */
class ContactsEntity extends BlockBase implements ContainerFactoryPluginInterface {

  const MODE_VIEW = 'view';
  const MODE_VIEW_NEW = 'view_new';
  const MODE_FORM = 'form';

  const EDIT_LINK_TITLE = 'title';
  const EDIT_LINK_CONTENT = 'content';

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
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity_display.repository')
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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $form_builder, CurrentRouteMatch $route_match, RequestStack $request_stack, EntityDisplayRepository $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;
    $this->request = $request_stack->getCurrentRequest();
    $this->entityDisplayRepository = $entity_display_repository;
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
    $op = $this->getOperation($entity);
    return $entity->access($op, NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function label($edit_link = FALSE) {
    $label = parent::label();

    if ($edit_link && $link = $this->getEditLink(self::EDIT_LINK_TITLE)) {
      if ($label) {
        $label = new FormattableMarkup('@label [@link]', [
          '@label' => $label,
          '@link' => $link->toString(),
        ]);
      }
      else {
        $label = $link->toString();
      }
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['mode'] = [
      '#type' => 'select',
      '#options' => [self::MODE_FORM => 'Form', self::MODE_VIEW => 'View'],
      '#title' => $this->t('Show as'),
      '#default_value' => $this->configuration['mode'],
    ];

    // @todo figure out how we can get the parent form structure.
    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->pluginDefinition['_entity_type_id']),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->configuration['view_mode'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[mode]"]' => array(
            'value' => self::MODE_VIEW,
          ),
        ),
      ),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
    $this->configuration['mode'] = $form_state->getValue('mode');
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
   * Get the edit link, if applicable.
   *
   * @return false|\Drupal\Core\Link
   *   The edit link, or FALSE if there is none.
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

    $params = $this->routeMatch->getRawParameters()->all();
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

    if ($this->getOperation($entity) == 'edit') {
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
   *   The operation to use, either 'view' or 'edit'.
   */
  protected function getOperation(EntityInterface $entity = NULL) {
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
    $bundle = $definition['_bundle_key'] ? $config['create'] : NULL;
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
    if ($definition['_bundle_key']) {
      $values[$definition['_bundle_key']] = $config['create'];
    }

    // Create the entity.
    $entity = $this->entityTypeManager->getStorage($definition['_entity_type_id'])->create($values);

    // If this has an owner, set it.
    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwner($user);
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
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name == 'contacts.ajax_subpage') {
      $route_name = 'page_manager.page_view_contacts_dashboard_contact';
    }
    $route_params = $this->routeMatch->getRawParameters()->all();
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

    $entity_type = $this->entityTypeManager->getDefinition($this->pluginDefinition['_entity_type_id']);

    // Add the module which defines the entity type.
    $dependencies['module'][] = $entity_type->getProvider();

    // If we have a bundle for creating, add it's config dependencies.
    if ($this->configuration['create'] && is_string($this->configuration['create'])) {
      $dependency = $entity_type->getBundleConfigDependency($this->configuration['create']);
      $dependencies[$dependency['type']][] = $dependency['name'];
    }

    return $dependencies;
  }

}
