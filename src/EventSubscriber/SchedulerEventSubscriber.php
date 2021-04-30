<?php

namespace Drupal\scheduler_content_moderation_integration\EventSubscriber;

use Drupal\scheduler\Event\SchedulerMediaEvents;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle scheduler events.
 *
 * The six Scheduler events for Node entities are:
 * SchedulerEvents::PRE_PUBLISH
 * SchedulerEvents::PUBLISH
 * SchedulerEvents::PRE_UNPUBLISH
 * SchedulerEvents::UNPUBLISH
 * SchedulerEvents::PRE_PUBLISH_IMMEDIATELY
 * SchedulerEvents::PUBLISH_IMMEDIATELY.
 *
 * The six Scheduler events for Media entities are:
 * SchedulerMediaEvents::PRE_PUBLISH
 * SchedulerMediaEvents::PUBLISH
 * SchedulerMediaEvents::PRE_UNPUBLISH
 * SchedulerMediaEvents::UNPUBLISH
 * SchedulerMediaEvents::PRE_PUBLISH_IMMEDIATELY
 * SchedulerMediaEvents::PUBLISH_IMMEDIATELY.
 */
class SchedulerEventSubscriber implements EventSubscriberInterface {

  /**
   * Operations to perform after Scheduler publishes a node immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    $node->set('moderation_state', $node->publish_state->getValue());
    $event->setNode($node);
  }

  /**
   * Operations to perform after Scheduler publishes a media item immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publishMediaImmediately(SchedulerEvent $event) {
    /** @var Drupal\Core\Entity\EntityInterface $entity */
    $entity = $event->getEntity();
    $entity->set('moderation_state', $entity->publish_state->getValue());
    $event->setNode($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names above.
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    $events[SchedulerMediaEvents::PUBLISH_IMMEDIATELY][] = ['PublishMediaImmediately'];
    return $events;
  }

}
