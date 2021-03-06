<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to edit Contact tab entities.
 */
class ContactTabForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\contacts\Entity\ContactTabInterface $contact_tab */
    $contact_tab = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add contact tab');
    }
    else {
      $form['#title'] = $this->t('Edit %label tab', ['%label' => $contact_tab->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $contact_tab->label(),
      '#description' => $this->t("Label for the tab."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $contact_tab->id(),
      '#machine_name' => [
        'exists' => '\Drupal\contacts\Entity\ContactTab::load',
      ],
      '#disabled' => !$contact_tab->isNew(),
    ];

    /* @var \Symfony\Component\Routing\Route $route */
    $route = \Drupal::service('router.route_provider')->getRouteByName('page_manager.page_view_contacts_dashboard_contact');
    $form['path'] = [
      '#type' => 'textfield',
      '#default_value' => $contact_tab->getPath(),
      '#title' => $this->t('Path'),
      '#description' => $this->t('Can only contain lowercase letters, numbers and hyphens.'),
      '#required' => TRUE,
      '#field_prefix' => substr($route->getPath(), 0, -9),
    ];

    // This depends on https://www.drupal.org/node/2865059.
    $form['path']['#type'] = 'machine_name';
    $form['path']['#machine_name'] = [
      'exists' => '\Drupal\contacts\Entity\ContactTab::loadByPath',
      'label' => $form['path']['#title'],
      'replace_pattern' => '[^a-z0-9\-]+',
      'replace' => '-',
      'error' => $this->t('The path must be unique and contain only lowercase letters, numbers, and hyphens.'),
      'standalone' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contact_tab = $this->entity;
    $status = $contact_tab->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Contact tab.', [
          '%label' => $contact_tab->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Contact tab.', [
          '%label' => $contact_tab->label(),
        ]));
    }
    $form_state->setRedirectUrl($contact_tab->toUrl('collection'));
  }

}
