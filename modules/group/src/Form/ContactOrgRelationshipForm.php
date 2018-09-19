<?php

namespace Drupal\contacts_group\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for an organisation relationship.
 */
class ContactOrgRelationshipForm extends ContentEntityForm {

  /**
   * The Group Content entity.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $entity;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a ContactOrgRelationshipFormBlock object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, GroupMembershipLoaderInterface $membership_loader = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    if (!$membership_loader) {
      throw new InvalidArgumentException('The group membership loader service is missing.');
    }
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $group = $this->entity->getGroup();
    $member = $this->entity->getEntity();

    // If this is a join form with predefined entities, check that the
    // relationship doesn't already exist.
    if ($group && $member) {
      $plugin = $this->entity->getContentPlugin();
      $existing = $group->getContentByEntityId($plugin->getPluginId(), $member->id());
      if ($this->entity->isNew() && $existing) {
        if ($member->id() == $this->currentUser()->id()) {
          $member_label = $this->t('You');
        }
        else {
          $member_label = new FormattableMarkup('%label', ['%label' => $member->label()]);
        }
        return [
          '#markup' => $this->t('@member have already joined %group.', [
            '@member' => $member_label,
            '%group' => $group->label(),
          ]),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $group = $this->entity->getGroup();
    $member = $this->entity->getEntity();

    // Show a selection for the organisation for a new relationship.
    if (!$group) {
      $form['organisation'] = [
        '#type' => 'entity_autocomplete',
        '#required' => TRUE,
        '#title' => $this->t('Organisation'),
        '#target_type' => 'user',
        '#selection_handler' => 'search_api',
        '#selection_settings' => [
          'index' => 'contacts_index',
          'conditions' => [
            ['roles', 'crm_org'],
          ],
        ],
        '#weight' => -99,
      ];

      // Prevent joining yourself.
      if ($member) {
        $form['organisation']['#selection_settings']['conditions'][] = [
          'uid',
          $member->id(),
          '<>',
        ];
      }
    }

    // Show selection for the member for a new relationship.
    if (!$member) {
      $element = &$form['entity_id']['widget'][0]['target_id'];

      // Adjust the existing form element.
      $element['#title'] = $this->t('Member');
      unset($element['#description']);
      $element['#selection_handler'] = 'search_api';
      $element['#selection_settings'] = [
        'index' => 'contacts_index',
      ];

      // If the form state has been set up to restrict to certain roles from the
      // member finder (e.g. only individuals or only organisations) apply the
      // conditions to the selection.
      if ($roles = $form_state->get('member_roles')) {
        $element['#selection_settings']['conditions'][] = [
          'roles',
          $roles,
          'IN',
        ];
      }

      // Prevent adding yourself.
      if ($group) {
        $element['#selection_settings']['conditions'][] = [
          'uid',
          $group->contacts_org->target_id,
          '<>',
        ];
      }
    }
    // Don't allow changing the member of existing relationships.
    else {
      // Hide the user selection.
      $form['entity_id']['#access'] = FALSE;
    }

    // Hide the roles field if there are no roles.
    if (empty($form['group_roles']['widget']['#options'])) {
      $form['group_roles']['#access'] = FALSE;
    }

    // If we have the bypass group access permission, override the access on the
    // group role field.
    // @todo: Replace this by patching group_entity_field_access to check the
    // bypass permission before it forbids.
    elseif ($this->currentUser()->hasPermission('bypass group access')) {
      $form['group_roles']['#access'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\group\Entity\GroupContentInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    if ($entity->isNew()) {
      $org_id = $form_state->getValue('organisation');
      if ($org_id) {
        $org = $this->entityTypeManager->getStorage('user')->load($org_id);
        $form_state->set('group', $org->group->entity);
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($group = $form_state->get('group')) {
      $group->save();
      $this->entity->set('gid', $group->id());
    }
    return parent::save($form, $form_state);
  }

}
