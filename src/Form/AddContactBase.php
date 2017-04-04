<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Base class for the add contact forms.
 */
abstract class AddContactBase extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $widgetManager;

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Validation violations for the user entity.
   *
   * @var \Drupal\Core\Entity\EntityConstraintViolationListInterface
   */
  protected $userViolations;

  /**
   * The profile entity.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * Validation violations for the profile entity.
   *
   * @var \Drupal\Core\Entity\EntityConstraintViolationListInterface
   */
  protected $profileViolations;

  /**
   * Construct the add contact form object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
   *   The widget manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager, WidgetPluginManager $widget_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#parents'] = [];

    $user_fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $user = $this->entityTypeManager->getStorage('user')->create();
    $user_fields['mail']->setRequired(TRUE);
    $form['mail'] = $this->getWidget($user, $user_fields, 'mail', $form, $form_state);
    $form['mail']['#weight'] = 1;

    $form['actions'] = ['#type' => 'actions'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->buildEntities($form, $form_state);

    $this->userViolations = $this->user->validate();
    $this->userViolations->filterByFields(['mail']);
    $this->profileViolations = $this->profile->validate();

    $this->user->setValidationRequired(FALSE);
    $this->profile->setValidationRequired(FALSE);
    $form_state->setTemporaryValue('entity_validated', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->buildEntities($form, $form_state);
    $this->user->save();
    $this->profile->setOwner($this->user);
    $this->profile->save();
    $form_state->setRedirect('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $this->user->id(),
    ]);
  }

  /**
   * Retrieve the widget form element.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity we are working with.
   * @param \Drupal\field\Entity\FieldConfig[] $field_definitions
   *   The field definitions for the given entity.
   * @param string $field_name
   *   The field name to get the widget for.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The widget form array.
   */
  protected function getWidget(FieldableEntityInterface $entity, array $field_definitions, $field_name, array $form, FormStateInterface $form_state) {
    $widget = $this->widgetManager->getInstance([
      'field_definition' => $field_definitions[$field_name],
      'form_mode' => 'default',
    ]);
    $items = $entity->get($field_name);
    $items->filterEmptyItems();
    return $widget->form($items, $form, $form_state);
  }

  /**
   * Copy form values onto our entities.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildEntities(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->create();
    $user->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));
    $this->user = $user;
    $this->user->set('mail', $form_state->getValue('mail'));
  }

  /**
   * Flags violations for the current form.
   *
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function flagViolations(array $form, FormStateInterface $form_state) {
    // Flag entity level violations.
    foreach ($this->userViolations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setErrorByName('', $violation->getMessage());
    }
    foreach ($this->profileViolations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setErrorByName('', $violation->getMessage());
    }

    // Flag user mail violations.
    foreach ($this->userViolations->getByField('mail') as $violation) {
      $form_state->setErrorByName('mail', $violation->getMessage());
    }
  }

}
