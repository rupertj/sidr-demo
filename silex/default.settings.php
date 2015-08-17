<?php

$settings = [

  // Ensure this is disabled in live. It's a performance killer.
  'twig_debug' => FALSE,

  // Display timings of requests to the back end.
  'show_timer' => FALSE,

  // Passes cookie to backend to enable xdebug.
  'xdebug_backend_requests' => FALSE,

  // Silex's Debug mode on/off:
  'silex_debug' => FALSE,

  // Show Guzzle's debug.
  'debug_backend_requests' => FALSE,

  // Passed through to Solarium.
  'solr' => [
    'endpoint' => [
      'localhost' => [
        'host' => 'localhost',
        'port' => 8080,
        'path' => '/solr',
        'core' => 'core_name',
      ],
    ],
  ],

  // Settings for DrupalClient
  'drupal' => [
    'drupal.backend' => 'http://drupal.local',
    'drupal.endpoint' => 'services',
  ],

  // Passed through to memcache.
  'memcache' => [
    'memcache.class' => '\Memcached',
    'memcache.default_duration' => 1000,
    'memcache.default_compress' => 1,
  ],

];
