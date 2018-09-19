<?php

namespace Drupal\contacts_group\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\contacts\Dashboard;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an Organisation relationship form block.
 *
 * @Block(
 *   id = "contacts_org_relationship_form",
 *   category = @Translation("Dashboard Blocks"),
 *   admin_label = @Translation("Organisation relationship form"),
 *   dashboard_block = TRUE,
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *   },
 * )
 */
class ContactOrgRelationshipFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The Contacts Dashboard helper.
   *
   * @var \Drupal\contacts\Dashboard
   */
  protected $dashboard;

  /**
   * Constructs a new ContactOrgRelationshipFormBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder
   *   The entity form builder.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\contacts\Dashboard $dashboard
   *   The contacts dashboard helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $form_builder, RequestStack $request_stack, Dashboard $dashboard) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->request = $request_stack->getCurrentRequest();
    $this->dashboard = $dashboard;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('request_stack'),
      $container->get('contacts.dashboard')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'query_key' => 'org',
      'provides' => 'member',
      'member_roles' => NULL,
      'show_add' => TRUE,
      'add_title' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    /* @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');
    if ($this->configuration['provides'] != 'group' || $user->hasRole('crm_org')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $content = $this->getGroupContent();
    if (!$content) {
      return '';
    }

    switch ($this->configuration['provides']) {
      case 'member':
        $entity = $content->getGroup();
        break;

      case 'group':
        $entity = $content->getEntity();
        break;
    }

    return $entity ? $entity->label() : $this->t('Add relationship');
  }

  /**
   * Get the URL for the block.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  protected function getUrl() {
    // If this is the dashboard, get the full page URL.
    if ($this->dashboard->isDashboard()) {
      return $this->dashboard->getFullUrl();
    }

    // Otherwise use the current URL.
    return Url::fromRoute('<current>', [
      'user' => $this->getContextValue('user')->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // If we have a param, show the form.
    if ($group_content = $this->getGroupContent()) {
      return $this->buildForm($group_content);
    }

    // Otherwise show the add link.
    $build = [];

    $url = $this->getUrl()
      ->setOption('query', [$this->configuration['query_key'] => 'add']);

    if ($this->configuration['show_add']) {
      $build['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add relationship'),
        '#url' => $url,
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
      if ($this->configuration['provides'] == 'group') {
        if ($member_roles = $this->configuration['member_roles']) {
          $filter_indiv = in_array('crm_indiv', $member_roles);
          $filter_orgs = in_array('crm_org', $member_roles);
          if ($filter_indiv && !$filter_orgs) {
            $build['add']['#title'] = $this->t('Add individual');
          }
          elseif ($filter_orgs && !$filter_indiv) {
            $build['add']['#title'] = $this->t('Add member organisation');
          }
        }
      }
      else {
        $build['add']['#title'] = $this->t('Add organisation');
      }

      if ($this->configuration['add_title']) {
        $build['add']['#title'] = $this->configuration['add_title'];
      }
    }

    return $build;
  }

  /**
   * Create a new group for the user context.
   *
   * @return \Drupal\group\Entity\GroupContentInterface|false
   *   The group content entity or FALSE if there isn't one or we were unable to
   *   load it.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   Thrown if a the relationship isn't valid for the user context.
   */
  protected function getGroupContent() {
    $relationship = $this->request->query->get($this->configuration['query_key']);
    if (!$relationship) {
      return FALSE;
    }
    elseif ($relationship == 'add') {
      /* @var \Drupal\group\Entity\GroupType $group_type */
      $group_type = $this->entityTypeManager
        ->getStorage('group_type')
        ->load('contacts_org');
      $plugin = $group_type->getContentPlugin('group_membership');

      $values = [
        'type' => $plugin->getContentTypeConfigId(),
      ];
      $user = $this->getContextValue('user');
      if ($this->configuration['provides'] == 'member') {
        $values['entity_id'] = $user;
      }
      else {
        $values['gid'] = $user->group;
      }

      return $this->entityTypeManager
        ->getStorage('group_content')
        ->create($values);
    }
    else {
      /* @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = $this->entityTypeManager
        ->getStorage('group_content')
        ->load($relationship);

      if (!$group_content) {
        drupal_set_message($this->t('Unable to find the relationship to edit.'), 'error');
        return FALSE;
      }

      $expected_id = $this->configuration['provides'] == 'member' ?
        $group_content->getEntity()->id() :
        $group_content->getGroup()->contacts_org->target_id;
      if ($this->getContextValue('user')->id() != $expected_id) {
        throw new ContextException('Invalid context for relationship.');
      }

      return $group_content;
    }
  }

  /**
   * Build the membership form.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to edit or a new one for creation.
   *
   * @return array
   *   The form render array.
   */
  protected function buildForm(GroupContentInterface $group_content) {
    // Manually build our action and redirect.
    $query = $this->request->query->all();
    // @see \Drupal\Core\Form\FormBuilder::buildFormAction.
    unset($query[FormBuilder::AJAX_FORM_REQUEST], $query[MainContentViewSubscriber::WRAPPER_FORMAT]);

    // Build our URLs.
    $action = $this->getUrl()
      ->setOption('query', $query);
    unset($query[$this->configuration['query_key']]);
    $redirect = $this->getUrl()
      ->setOption('query', $query);

    // Get the form render array with the right redirect and action.
    $form = $this->formBuilder->getForm($group_content, 'contacts-org', [
      'redirect' => $redirect,
      'member_roles' => $this->configuration['member_roles'],
    ]);
    $form['#action'] = $action->toString();

    // Add a cancel to take us back to the page.
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->getUrl(),
      '#weight' => 99,
    ];

    return $form;
  }

}
