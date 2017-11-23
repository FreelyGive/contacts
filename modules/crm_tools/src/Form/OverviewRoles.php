<?php

namespace Drupal\crm_tools\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides roles overview form for the roles page.
 */
class OverviewRoles extends FormBase {

  /**
   * The role storage handler.
   *
   * @var \Drupal\crm_tools\AdvancedRoleStorageInterface
   */
  protected $storageController;

  /**
   * Constructs an OverviewRoles object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->storageController = $entity_type_manager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_overview_roles';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parent_fields = FALSE;

    // An array of the roles to be displayed on this page.
    $current_page = [];

    $tree = $this->storageController->loadTree();
    $tree_index = 0;
    do {
      // In case this tree is completely empty.
      if (empty($tree[$tree_index])) {
        break;
      }

      // Do not let a role start the page that is not at the root.
      $role = $tree[$tree_index];

      $current_page[$role->id()] = $role;
    } while (isset($tree[++$tree_index]));

    // If this form was already submitted once, it's probably hit a validation
    // error. Ensure the form is rebuilt in the same order as the user
    // submitted.
    $user_input = $form_state->getUserInput();
    if (!empty($user_input)) {
      // Get the POST order.
      $order = array_flip(array_keys($user_input['roles']));
      // Update our form with the new order.
      $current_page = array_merge($order, $current_page);
      foreach ($current_page as $key => $role) {
        // Verify this is a role for the current page and set at the current
        // depth.
        if (is_array($user_input['roles'][$key]) && is_numeric($user_input['roles'][$key]['role']['id'])) {
          $current_page[$key]->depth = $user_input['roles'][$key]['role']['depth'];
        }
        else {
          unset($current_page[$key]);
        }
      }
    }

    $errors = $form_state->getErrors();
    $destination = $this->getDestinationArray();
    $row_position = 0;
    // Build the actual form.
    $form['roles'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No Roles available. <a href=":link">Add role</a>.', [':link' => $this->url('user.role_add')]),
    ];
    foreach ($current_page as $key => $role) {
      /* @var \Drupal\user\RoleInterface $role */
      $form['roles'][$key]['#role'] = $role;
      $indentation = [];
      if (isset($role->depth) && $role->depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $role->depth,
        ];
      }
      $form['roles'][$key]['role'] = [
        '#prefix' => !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '',
      ];
      if ($role->getThirdPartySetting('crm_tools', 'crm_tools_is_hat', FALSE)) {
        $form['roles'][$key]['role']['icon'] = [
          '#type' => 'open_iconic',
          '#size' => '10',
        ];
        if ($role->getThirdPartySetting('crm_tools', 'crm_tools_icon', FALSE)) {
          $form['roles'][$key]['role']['icon']['#icon'] = $role->getThirdPartySetting('crm_tools', 'crm_tools_icon');
        }
        if ($role->getThirdPartySetting('crm_tools', 'crm_tools_color', FALSE)) {
          $form['roles'][$key]['role']['icon']['#color'] = $role->getThirdPartySetting('crm_tools', 'crm_tools_color');
        }
      }
      $form['roles'][$key]['role']['label'] = [
        '#type' => 'link',
        '#title' => $role->label(),
        '#url' => $role->urlInfo(),
      ];
      if (count($tree) > 1) {
        $parent_fields = TRUE;
        $form['roles'][$key]['role']['id'] = [
          '#type' => 'hidden',
          '#value' => $role->id(),
          '#attributes' => [
            'class' => ['role-id'],
          ],
        ];
        $form['roles'][$key]['role']['parent'] = [
          '#type' => 'hidden',
          // Yes, default_value on a hidden. It needs to be changeable by the
          // javascript.
          '#default_value' => $role->parents[0],
          '#attributes' => [
            'class' => ['role-parent'],
          ],
        ];
        $form['roles'][$key]['role']['depth'] = [
          '#type' => 'hidden',
          // Same as above, the depth is modified by javascript, so it's a
          // default_value.
          '#default_value' => $role->depth,
          '#attributes' => [
            'class' => ['role-depth'],
          ],
        ];
      }
      $form['roles'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for added role'),
        '#title_display' => 'invisible',
        '#default_value' => $role->getWeight(),
        '#attributes' => [
          'class' => ['role-weight'],
        ],
      ];
      $operations = [
        'edit' => [
          'title' => $this->t('Edit'),
          'query' => $destination,
          'url' => $role->urlInfo('edit-form'),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'query' => $destination,
          'url' => $role->urlInfo('delete-form'),
        ],
      ];

      $form['roles'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $form['roles'][$key]['#attributes']['class'] = [];
      if ($parent_fields) {
        $form['roles'][$key]['#attributes']['class'][] = 'draggable';
      }

      // Add an error class if this row contains a form error.
      foreach ($errors as $error_key => $error) {
        if (strpos($error_key, $key) === 0) {
          $form['roles'][$key]['#attributes']['class'][] = 'error';
        }
      }
      $row_position++;
    }

    if ($parent_fields) {
      $form['roles']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'role-parent',
        'subgroup' => 'role-parent',
        'source' => 'role-id',
        'hidden' => FALSE,
      ];
      $form['roles']['#tabledrag'][] = [
        'action' => 'depth',
        'relationship' => 'group',
        'group' => 'role-depth',
        'hidden' => FALSE,
      ];
    }
    $form['roles']['#tabledrag'][] = [
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'role-weight',
    ];

    if (count($tree) > 1) {
      $form['actions'] = ['#type' => 'actions', '#tree' => FALSE];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $changed_roles = [];
    $tree = $this->storageController->loadTree();

    if (empty($tree)) {
      return;
    }

    $roles = [];
    foreach ($tree as $role) {
      $roles[$role->id()] = $role;
    }

    $weight = 0;
    foreach ($form_state->getValue('roles') as $id => $values) {
      if (isset($roles[$id])) {
        /* @var \Drupal\user\RoleInterface $role */
        $role = $roles[$id];
        // Set the weight in order regardless of hierarchy.
        if ($role->getWeight() != $weight) {
          $role->setWeight($weight);
          $changed_roles[$role->id()] = $role;
        }

        // Update any changed parents.
        if ($values['role']['parent'] !== $role->getThirdPartySetting('crm_tools', 'crm_tools_parent', "0")) {
          $role->setThirdPartySetting('crm_tools', 'crm_tools_parent', $values['role']['parent']);
          $changed_roles[$role->id()] = $role;
        }
        $weight++;
      }
    }

    // Save all updated roles.
    foreach ($changed_roles as $role) {
      $role->save();
    }

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
