<?php

namespace Drupal\crm_tools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class ToolsForm.
 */
class ToolsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $manager;
  /**
   * Constructs a new ToolsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    parent::__construct($config_factory);
    $this->manager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'crm_tools.tools',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_tools_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('crm_tools.tools');

    $form['tools'] = [
      '#type' => 'fieldset',
      '#title' => 'Tools',
    ];

    $form['tools']['hats'] = [
      '#type' => 'checkbox',
      '#title' => 'Contact Types (Advanced Roles)',
      '#description' => 'Extend Drupal roles to enable different types of CRM Contacts.',
      '#default_value' => $config->get('active_tools')['hats'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $active_tools = [
      'hats' => $form_state->getValue('hats'),
    ];

    $this->config('crm_tools.tools')
      ->set('active_tools', $active_tools)
      ->save();
  }

}
