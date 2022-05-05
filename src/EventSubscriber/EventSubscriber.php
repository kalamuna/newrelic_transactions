<?php

/**
 * @file
 * Contains \Drupal\newrelic_transactions\EventSubscriber\EventSubscriber.
 */

namespace Drupal\newrelic_transactions\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Event subscriber that responds to page requests.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Config Factory for loading config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

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
  public function nameTransaction(RequestEvent $event) {
    // Only change New Relic data if New Relic is actually enabled.
    if (!extension_loaded('newrelic')) {
      return;
    }

    // Config object.
    $config = $this->configFactory->get('newrelic_transactions.config');

    // We are going to use the router path to name transactions.
    $route_match = \Drupal::routeMatch();
    $route = $route_match->getRouteObject();
    $path = $route->getPath();

    // If this is an entity, replace the entity type with the bundle.
    foreach ($route_match->getParameters() as $key => $item) {
      if (method_exists($item, 'bundle')) {
        $path = str_replace('{' . $key . '}', '{' . $item->bundle() . '}', $path);
      }
    }

    // Load roles for role order.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $role_ids = array_keys($roles);
    $roles = array_combine($role_ids, $role_ids);

    // Roles that can be passed to New Relic.
    $transaction_roles = $config->get('transaction_roles');
    $transaction_roles = array_filter($transaction_roles);

    // Filter roles by transaction roles from config.
    if (!empty($transaction_roles)) {
      $roles = array_filter($roles, function ($role) use ($transaction_roles) {
        return array_key_exists($role, $transaction_roles) ?? FALSE;
      });
    }

    // We are going to prepend a role to the transaction name,
    // since that can greatly affect performance.
    $user_roles = \Drupal::currentUser()->getRoles();

    // Filter roles by what is set in config.
    $user_roles = array_filter($roles, function ($role) use ($user_roles) {
      return in_array($role, $user_roles);
    });
    $user_roles = array_values($user_roles);

    // Select the highest-weighted role for the current user.
    $transaction_role = array_pop($user_roles);

    // Tell New Relic to change the transaction name (without the starting /).
    newrelic_name_transaction(substr($path, 1) . ' (' . $transaction_role . ')');
  }

  /**
   * Specify the name of this transation in New Relic based on routing and user info.
   */
  public function addAttributes(RequestEvent $event) {
    // Only change New Relic data if New Relic is actually enabled.
    if (!extension_loaded('newrelic')) {
      return;
    }

    // Config object.
    $config = $this->configFactory->get('newrelic_transactions.config');

    // Get user_data config.
    $user_data = $config->get('user_data');

    // Track some data about the current user
    // so we can identify who is having trouble.
    $user = \Drupal::currentUser();

    if ($user_data['id']) {
      newrelic_add_custom_parameter('user_id', $user->id());
    }
    if ($user_data['roles']) {
      newrelic_add_custom_parameter('user_roles', implode(', ', $user->getRoles()));
    }
  }

}
