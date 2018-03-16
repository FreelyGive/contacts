<?php

namespace Drupal\contacts\Form;

use Drupal\contacts\ContactsTabManager;
use Drupal\contacts\Controller\DashboardRebuildTrait;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form for dashboard blocks.
 */
class DashboardTabConfigureForm extends FormBase {

  use AjaxFormHelperTrait;
  use DashboardRebuildTrait;

  /**
   * The dashboard tab.
   *
   * @var \Drupal\contacts\Entity\ContactTab
   */
  protected $tab;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  protected $tabManager;

  /**
   * Construct the add contact form object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The contacts tab manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ContactsTabManager $tab_manager, ClassResolverInterface $class_resolver) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tabManager = $tab_manager;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('contacts.tab_manager'),
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "dashboard_tab_configure_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->tab = $form_state->getBuildInfo()['args'][0];
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->tab->label(),
      '#description' => $this->t("Label for the tab."),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#limit_validation_errors' => [],
    ];

    $form['actions']['remove'] = [
      '#type' => 'submit',
      '#value' => 'Remove',
      '#name' => 'remove',
      '#submit' => [[$this, 'removeTab']],
      '#limit_validation_errors' => [],
    ];

    // Add ajax to actions.
    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      $form['actions']['cancel']['#ajax']['callback'] = '::ajaxSubmit';
      $form['actions']['remove']['#ajax']['callback'] = '::ajaxSubmit';
    }

    $form['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::entityTypeManager()
      ->getFormObject('contact_tab', 'edit')
      ->setEntity($this->tab)
      ->buildEntity($form, $form_state)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeTab(array &$form, FormStateInterface $form_state) {
    $this->tab->delete();
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#name'] == 'remove') {
      $this->tab = $this->tabManager->getTab('summary');
    }
    return $this->rebuildAndReturn($this->tab);
  }

}
