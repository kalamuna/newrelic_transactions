<?php

namespace Drupal\newrelic_transactions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure New Relic Transactions config for this site.
 */
class NewRelicTransactionsConfig extends ConfigFormBase {

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
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newrelic_transactions_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['newrelic_transactions.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Config object.
    $config = $this->config('newrelic_transactions.config');

    if (!extension_loaded('newrelic')) {
      \Drupal::messenger()->addError('New Relic extension not found');
    }

    // Create list of role options.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $role_options = array_map(function($role) {
      return $role->label();
    }, $roles);

    // Create list of user data attribute options.
    $data_options = [
      'id' => 'ID',
      'roles' => 'Roles',
    ];

    // Roles to pass to transactions.
    $default = $config->get('transaction_roles');
    $form['transaction_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Transaction Roles'),
      '#default_value' => $default ?? NULL,
      '#options' => $role_options,
      '#description' => $this->t('User\'s highest weight role will be used in the transaction. Go to <a href="/admin/people/roles">Roles</a> to reorder.'),
    ];

    // User data to pass as custom parameter.
    $default = $config->get('user_data');
    $form['user_data'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('User Data Parameters'),
      '#default_value' => $default ?? NULL,
      '#options' => $data_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get an array of form values.
    $values = $form_state->getValues();
    $config = \Drupal::configFactory()->getEditable('newrelic_transactions.config');

    $ignore_keys = [
      '_core',
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];

    // Loop through the values and save them to configuration.
    foreach ($values as $value_key => $value) {
      if (in_array($value_key, $ignore_keys)) {
        continue;
      }
      $config->set($value_key, $value)->save();
    }

    parent::submitForm($form, $form_state);
  }

}
