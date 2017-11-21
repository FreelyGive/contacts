<?php

namespace Drupal\crm_tools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for activating crm tools.
 */
class ToolsForm extends ConfigFormBase {

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
