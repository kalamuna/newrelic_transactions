<?php

/**
 * @file
 * Contains \Drupal\newrelic_transactions\EventSubscriber\EventSubscriber.
 */

namespace Drupal\newrelic_transactions\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that responds to page requests.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['nameTransaction'];
    $events[KernelEvents::REQUEST][] = ['addAttributes'];
    return $events;
  }

  /**
   * Specify the name of this transation in New Relic based on routing and user info.
   */
  public function nameTransaction(GetResponseEvent $event) {

    // Only change New Relic data if New Relic is actually enabled.
    if (!extension_loaded('newrelic')) return;

    // We are going to use the router path to name transactions.
    $route_match = \Drupal::routeMatch();
    $route = $route_match->getRouteObject();
    $path = $route->getPath();

    // If this is an entity, replace the entity type with the bundle.
    foreach($route_match->getParameters() as $key => $item) {
      if(method_exists($item, 'bundle')) {
        $path = str_replace('{'.$key.'}', '{'.$item->bundle().'}', $path);
      }
    }

    // We are going to prepend a role to the transaction name, since that can greatly affect performance.
    $user_roles = \Drupal::currentUser()->getRoles();
    // Select the highest-weighted role for the current user. (This should probably become configurable.)
    $transaction_role = array_pop($user_roles);

    // Tell New Relic to change the transaction name (without the starting /).
    newrelic_name_transaction(substr($path, 1) . ' (' . $transaction_role .')');
  }

  /**
   * Specify the name of this transation in New Relic based on routing and user info.
   */
  public function addAttributes(GetResponseEvent $event) {

    // Only change New Relic data if New Relic is actually enabled.
    if (!extension_loaded('newrelic')) return;

    // Track some data about the current user so we can identify who is having trouble.
    $user = \Drupal::currentUser();
    newrelic_add_custom_parameter('user_id', $user->id());
    newrelic_add_custom_parameter('user_roles', implode(', ', $user->getRoles()));

    // Not sharing username and mail by default to avoid data retention and privacy issues.
    //newrelic_add_custom_parameter('user_name', $user->getAccountName());
    //newrelic_add_custom_parameter('user_mail', $user->getEmail());
  }
}
