<?php

namespace Drupal\contacts\Event;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Render\Element;
use Symfony\Component\EventDispatcher\Event;


/**
 * Event raised to retrieve list of properties shown during merging.
 *
 * This event can be dispatched to retreive the properties that should be
 * shown on the deduplication page.
 *
 * @package Drupal\contacts\Event
 */
class GetPropertiesToShowOnDedupScreenEvent extends Event {
  const EVENT_NAME = 'contacts.dedup_property_info';


  public $properties;

  /**
   * GetPropertiesToShowOnDedupScreenEvent constructor.
   */
  public function __construct() {
    $this->properties = array(
      NULL => array(
        '#weight' => -99,
//        'party_hat' => array(
//          'render field' => TRUE,
//        ),
        'mail' => array(
          'render field' => TRUE,
          'compare' => TRUE,
        ),
        'profile_crm_indiv:entity:crm_gender' => [
          'render field' => TRUE,
          'compare' => TRUE
        ]
//        'party_indiv_to_org' => array(
//          'use label' => TRUE,
//        ),
      ),
    );
  }

  /**
   * Get a list of properties to show on the dedup verification page.
   *
   * @return array
   *   An array of properties to show in the comparison. Outer keys are group
   *   labels (NULL is no group). Keys are property strings (a chain of metadata
   *   wrapper properties separated by :). Values are arrays containing:
   *   - label: Optional string to override the normal label.
   *   - use label: Optionally attempt to use the EntityValueWrapper::label().
   *   - render field: If the property is a field, setting this to TRUE will
   *     render the property via the Field API.
   *   - compare: A boolean indicating whether to run a comparison or a callable
   *     for a custom comparison function. See callback_opencrm_dedupe_compare().
   *   - strip html: TRUE if HTML should be stripped from the basic comparison.
   *     Defaults to TRUE.
   */
  public function getPropertiesByWeight() {
    // Sort everything by weight.
    $weight = 0;
    $info = &$this->properties;

    foreach ($this->properties as &$properties) {
      // Set a default weight on the group.
      $properties += array('#weight' => $weight++ / 100);

      // Set up our defaults on the properties.
      foreach (Element::children($properties) as $property) {
        $properties[$property] += array(
          '#weight' => $weight++ / 100,
          'use label' => FALSE,
          'render field' => FALSE,
          'compare' => TRUE,
          'strip html' => TRUE,
        );
      }

      // Sort the propreties.
      uasort($properties, [SortArray::class, 'sortByWeightProperty']);

      // Remove the weight.
      foreach (Element::children($properties) as $property) {
        unset($properties[$property]['#weight']);
      }
    }

    // Sort the group.
    uasort($info, [SortArray::class, 'sortByWeightProperty']);

    // Remove group weights.
    foreach ($info as &$properties) {
      unset($properties['#weight']);
    }

    return $info;

  }

  /**
   * Dispatches the event.
   *
   * @return static
   */
  public static function dispatch() {
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = new static();
    $dispatcher->dispatch(GetPropertiesToShowOnDedupScreenEvent::EVENT_NAME, $event);
    return $event;
  }


}