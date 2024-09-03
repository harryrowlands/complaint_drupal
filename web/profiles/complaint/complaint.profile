<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

/**
 * Implements hook_install().
 */

function complaint_profile_install() {
  $config_factory = \Drupal::configFactory();

  $config_factory->getEditable('rest.resource.create_investigation_resource')
    ->setData([
      'id' => 'create_investigation_resource',
      'plugin_id' => 'create_investigation_resource',
      'granularity' => 'resource',
      'plugin_configuration' => [],
      'dependencies' => [],
      'status' => TRUE,
      'configuration' => [
        'methods' => ['POST'],
        'accept' => ['application/json'],
        'contentType' => ['application/json'],
        'formats' => ['json'],
      ],
    ])->save();
}