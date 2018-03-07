<?php

namespace Drupal\contacts\Form;

use Drupal\contacts\Event\GetPropertiesToShowOnDedupScreenEvent;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deduplicating/merging form.
 */
class DedupForm extends FormBase {

  protected $renderer;
  protected $messenger;
  protected $date_formatter;

  /**
   * DedupForm constructor.
   */
  public function __construct(RendererInterface $renderer, MessengerInterface $messenger, DateFormatterInterface $date_formatter) {
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->date_formatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('messenger'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "contacts_dedup_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $primary_contact_id = NULL, $secondary_contact_id = NULL) {
    if (!$primary_contact_id || !$secondary_contact_id) {
      $this->buildSelectionForm($form, $form_state);

      // Contact IDs may have been supplied by the previous form submission.
      $primary_contact_id = !empty($form_state->getValue('selection')['primary_party']) ? $form_state->getValue('selection')['primary_party'] : NULL;
      $secondary_contact_id = !empty($form_state->getValue('selection')['secondary_party']) ? $form_state->getValue('selection')['secondary_party'] : NULL;
    }

    // Add our current selection (URL or form) for our merge submission.
    $form['primary_party'] = [
      '#type' => 'value',
      '#value' => $primary_contact_id,
    ];
    $form['secondary_party'] = [
      '#type' => 'value',
      '#value' => $secondary_contact_id,
    ];

    // If we don't have both values, there is nothing more to do.
    // Just render the selection form.
    if (!$primary_contact_id || !$secondary_contact_id) {
      return $form;
    }

    // If we're able to load our parties,
    // show a verification table with details.
    $this->buildMergeForm($form, $form_state, $primary_contact_id, $secondary_contact_id);

    return $form;
  }

  /**
   * Gets the values of the specified field form both primary and secondary.
   *
   * @param \Drupal\user\Entity\User $primary_contact
   *   Primary contact for merge.
   * @param \Drupal\user\Entity\User $secondary_contact
   *   Secondary contact for merge.
   * @param string $property
   *   Name of property to render.
   * @param array $info
   *   Associative array with metadata about the property.
   *
   * @return array
   *   Array containing 2 values (from primary and secondary contact).
   *
   * @throws \Exception
   */
  private function getFieldValuesToRender(User $primary_contact, User $secondary_contact, $property, array &$info) {
    // Get our final value.
    $v1_value = $v2_value = NULL;

    $parts = explode(':', $property);
    $final_part = array_pop($parts);

    $v1 = $primary_contact;
    $v2 = $secondary_contact;

    foreach ($parts as $part) {
      $v1 = $v1 && isset($v1->{$part}) ? $v1->{$part} : NULL;
      $v2 = $v2 && isset($v2->{$part}) ? $v2->{$part} : NULL;

      if ($v1 == NULL && $v2 == NULL) {
        return [];
      }
    }

    $property_info = $v1->get($final_part)->getFieldDefinition();

    // If not set, pull through the label.
    if (!isset($info['label'])) {
      $info['label'] = $property_info->getLabel();
    }

    $fieldConfig = FieldConfig::loadByName($v1->getEntityTypeId(), $v1->bundle(), $final_part);

    if ($fieldConfig != NULL && $info['render field']) {
      $displayOptions = $fieldConfig->getDisplayOptions('default');
      $displayOptions['label'] = 'hidden';

      if ($v1->{$final_part}->value !== NULL) {
        $v1_value = $v1->{$final_part}->view($displayOptions);
        $v1_value = $this->renderer->render($v1_value);
      }
      if ($v2->{$final_part}->value !== NULL) {
        $v2_value = $v2->{$final_part}->view($displayOptions);
        $v2_value = $this->renderer->render($v2_value);
      }
    }
    else {
      // TODO: label/value switch not implemented yet.
      // $method = $info['use label'] ? 'label' : 'value';
      // $v1_value = $v1->hasField($final_part) && isset($v1->{$final_part}) ? $v1->{$final_part}->{$method}() : NULL;
      // $v2_value = $v2 && $v2->value() && isset($v2->{$final_part}) ? $v2->{$final_part}->{$method}() : NULL;

      $v1_value = $v1->hasField($final_part) && isset($v1->{$final_part}) ? $v1->{$final_part}->value : NULL;
      $v2_value = $v2->hasField($final_part) && isset($v2->{$final_part}) ? $v2->{$final_part}->value : NULL;

      if (!$info['use label']) {
        // If we don't have a list,
        // wrap it in an array so we can process everything the same.
        $type = $property_info->getType();
        if (substr($type, 0, 5) != 'list<') {
          $v1_value = isset($v1_value) ? [$v1_value] : NULL;
          $v2_value = isset($v2_value) ? [$v2_value] : NULL;
        }
        else {
          $type = $this->extractListType($type);
        }

        // Now loop over and do any processing on the type.
        foreach (['v1_value', 'v2_value'] as $value) {
          if (is_array($$value)) {
            foreach ($$value as &$item) {
              switch ($type) {
                case 'date':
                  $item = $this->date_formatter->format($item);
                  break;
              }
            }
          }
        }

        // And convert back into a string.
        $v1_value = is_array($v1_value) ? implode(', ', $v1_value) : $v1_value;
        $v2_value = is_array($v2_value) ? implode(', ', $v2_value) : $v2_value;
      }
    }

    return [$v1_value, $v2_value];
  }

  /**
   * Builds the merge selection form.
   */
  private function buildSelectionForm(array &$form, FormStateInterface $form_state) {

    // Show the selection form.
    $form['selection'] = [
      '#type' => 'fieldset',
      '#title' => t('Select contacts to merge'),
      '#description' => t('Enter the IDs of the contacts you want to merge.'),
      '#tree' => TRUE,
    ];
    $form['selection']['#collapsible'] =
    $form['selection']['#collapsed'] = $form_state->isSubmitted();

    $form['selection']['primary_party'] = [
      '#type' => 'textfield',
      '#title' => t('Primary Contact Id'),
      '#description' => t('Data from the secondary contact will be merged into this contact.'),
      '#required' => TRUE,
    ];

    $form['selection']['secondary_party'] = [
      '#type' => 'textfield',
      '#title' => t('Duplicate contact id'),
      '#description' => t('Data from this contact will be merged into the primary contact.'),
      '#required' => TRUE,
    ];

    $form['selection']['actions'] = ['#type' => 'actions'];
    $form['selection']['actions']['verify'] = [
      '#type' => 'submit',
      '#name' => 'verify_merge',
      '#value' => t('Verify merge'),
    ];

  }

  /**
   * Builds the merge summary.
   *
   * @throws \Exception
   */
  private function buildMergeForm(array &$form, FormStateInterface $form_state, $primary_contact_id, $secondary_contact_id) {
    /* @var $primary_contact \Drupal\user\Entity\User */
    $primary_contact = User::load($primary_contact_id);
    /* @var $secondary_contact \Drupal\user\Entity\User */
    $secondary_contact = User::load($secondary_contact_id);

    if ($primary_contact && $secondary_contact) {
      $form['#attached']['library'][] = 'contacts/contacts_dedup_form';

      $form['merge'] = [
        '#type' => 'container',
      ];
      if (isset($form['selection'])) {
        $form['merge']['#states'] = [
          'visible' => [
            ':input[name="selection[primary_party]"]' => ['value' => $primary_contact->id()],
            ':input[name="selection[secondary_party]"]' => ['value' => $secondary_contact->id()],
          ],
        ];
      }

      $form['merge']['verify'] = [
        '#theme' => 'table',
        '#header' => [
          ['header' => FALSE],
          t('<strong>Primary</strong><br/>@label <small>[@pid]</small>', [
            '@label' => $primary_contact->label(),
            '@pid' => $primary_contact->id(),
          ]),
          t('<strong>Duplicate</strong><br/>@label <small>[@pid]</small>', [
            '@label' => $secondary_contact->label(),
            '@pid' => $secondary_contact->id(),
          ]),
        ],
        '#rows' => [],
      ];

      $form['merge']['config'] = [];

      // Capture a flag so that custom callbacks can prevent merges.
      $prevent_merge = FALSE;

      $property_groups = GetPropertiesToShowOnDedupScreenEvent::dispatch()
        ->getPropertiesByWeight();

      foreach ($property_groups as $group => $properties) {
        if ($group) {
          $group_header = [
            [
              'colspan' => 3,
              'header' => TRUE,
              'data' => $group,
            ],
          ];
        }

        foreach ($properties as $property => $info) {
          list($v1_value, $v2_value) = $this->getFieldValuesToRender($primary_contact, $secondary_contact, $property, $info);

          // If we don't have either value we can skip out.
          if ($v1_value === NULL && $v2_value === NULL) {
            continue;
          }

          // Output the group header if we have one.
          if (isset($group_header)) {
            $form['merge']['verify']['#rows'][$group] = $group_header;
            unset($group_header);
          }

          // Build our row.
          $row = [];
          $row['data'][0] = $info['label'];
          $row['data'][1] = $v1_value;
          $row['data'][2]['data'] = $v2_value;

          // Run the comparison if requested.
          if ($info['compare']) {
            $match = NULL;

            // If a callable, pass onto the custom comparison.
            if (is_callable($info['compare'])) {
              $prevent_this_merge = FALSE;
              $match = $info['compare']($v1_value, $v2_value, $prevent_this_merge, $form['merge']['config']);
              $prevent_merge = $prevent_merge || $prevent_this_merge;
            }
            // Otherwise perform a simple string comparison.
            elseif ($v1_value !== NULL && $v2_value !== NULL) {
              if ($info['strip html']) {
                $v1_value = isset($v1_value) ? strip_tags($v1_value) : $v1_value;
                $v2_value = isset($v2_value) ? strip_tags($v2_value) : $v2_value;
              }
              $match = $v1_value == $v2_value;
            }
            elseif ($v1_value === NULL && $v2_value) {
              $match = TRUE;
            }

            if (isset($match)) {
              $row['data'][2]['class'][] = 'contact-dedupe-' . ($match ? 'match' : 'conflict');
            }
          }

          // Add our row to the table.
          $form['merge']['verify']['#rows'][$property] = $row;
        }
      }

      $form['merge']['warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['messages', 'warning']],
        '#value' => t('The colour coding provides an indication of what is likely to happen during the merge process. Results may vary according to specific circumstances.'),
      ];

      if ($prevent_merge) {
        $form['merge']['prevented'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['messages', 'error']],
          '#value' => t('This merge has been prevented. Please check for any at the top of the page.'),
        ];
      }

      if (!empty($form['merge']['config'])) {
        $form['merge']['config']['#type'] = 'fieldset';
        $form['merge']['config']['#title'] = t('Additional settings');
        $form['merge']['config']['#tree'] = TRUE;
      }

      $form['merge']['actions'] = [
        '#type' => 'actions',
        '#weight' => 100,
        '#access' => !$prevent_merge,
      ];

      $form['merge']['actions']['submit'] = [
        '#type' => 'submit',
        '#name' => 'run_merge',
        '#value' => t('Run merge'),
      ];

      // TODO Batch process not yet implemented.
      /*// If we want to process immediately, add our batch submission handler.
      if (variable_get('opencrm_dedupe_batch', 1)) {
        $form['merge']['actions']['submit']['#submit'][] = 'opencrm_dedupe_party_merge_form_batch';
      }*/
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // When running a merge, make sure the user hasn't entered new IDs.
    if ($form_state->getTriggeringElement()["#name"] == 'run_merge') {
      // If we have the selection values, make sure they haven't been changed.
      $primary_id_entered = $form_state->getValue('selection')['primary_party'];
      $secondary_id_entered = $form_state->getValue('selection')['secondary_party'];

      $primary_id_verified = $form_state->getValue('primary_party');
      $secondary_id_verified = $form_state->getValue('secondary_party');

      if (!empty($primary_id_entered) && !empty($primary_id_verified)) {
        if ($primary_id_entered != $primary_id_verified) {
          $form_state->setErrorByName('selection][primary_party', t('Please verify your new selection.'));
        }
      }

      if (!empty($secondary_id_entered) && !empty($secondary_id_verified)) {
        if ($secondary_id_entered != $secondary_id_verified) {
          $form_state->setErrorByName('selection][secondary_party', t('Please verify your new selection.'));
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'run_merge') {
      $primary_contact_id = $form_state->getValue("primary_party");
      $secondary_contact_id = $form_state->getValue("secondary_party");
      \Drupal::messenger()->addStatus("Running merge for $primary_contact_id and $secondary_contact_id");
      $form_state->setRebuild();
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * Extracts the underlying type from a list type.
   */
  private function extractListType($type) {
    if (strpos($type, 'list<') === 0 && $type[strlen($type) - 1] == '>') {
      return substr($type, 5, -1);
    }
    return FALSE;
  }

}
