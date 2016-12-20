<?php

namespace Drupal\contacts\EventSubscriber;

use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Pre Export event to strip out.
 */
class UserContentExportSubscriber implements EventSubscriberInterface {

  /**
   * Strip out circular profile2 dependencies.
   *
   * @param \Drupal\default_content\Event\ExportEvent $event
   *   The event to process.
   */
  public function preExport(ExportEvent $event) {
    /* @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $entity = $event->getExportedEntity();

    if ($entity->bundle() !== 'user') {
      return;
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $types = \Drupal::entityTypeManager()->getStorage('profile_type')->loadMultiple();
    foreach ($types as $type => $info) {
      unset($entity->{'profile_' . $type});
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DefaultContentEvents::PRE_EXPORT][] = ['preExport'];
    return $events;
  }

}