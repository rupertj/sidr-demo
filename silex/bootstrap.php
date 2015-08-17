<?php

/**
 * @file bootstrap.php
 * This file is given as an example: it's yours to edit as you see fit.
 * EG, you may want to use extra services for your application, EG Symfony's
 * forms or your own custom service. You can add that service here. Or, you may
 * want to change what the existing registered services do, like adding a global
 * template var to twig.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Silex\Application;

require __DIR__ . '/settings.php';

$app = new Silex\Application();

$twig_options['twig.path'] = __DIR__ . '/templates';

if ($settings['twig_debug']) {
  $twig_options['twig.options'] = array('debug' => true);
}

$app->register(new Silex\Provider\TwigServiceProvider(), $twig_options);

if ($settings['twig_debug']) {
  $app['twig']->addExtension(new Twig_Extension_Debug());
}

// Register and start a session:
$app->register(new Silex\Provider\SessionServiceProvider());
$app['session']->start();

// Make Solr Available:
$app->register(new Dafiti\Silex\SolariumServiceProvider(), [
  'solarium.config' => $settings['solr'],
]);

// Register Drupal backend:
$app->register(new SIDR\DrupalServiceProvider(), $settings['drupal']);

// Pass globals to the page template.
$user = $app['session']->get('user', false);

if (!empty($user['name'])) {
  $username = $user['name'];
}
else {
  $username = '';
}

if (!empty($user['picture']['url'])) {
  $userpic = $user['picture']['url'];
}
else {
  $userpic = '';
}

$app['twig']->addGlobal('username', $username);
$app['twig']->addGlobal('userpic', $userpic);

$app['twig']->addGlobal('logged_in', (bool) $user);
$app['twig']->addGlobal('current_url', trim($_SERVER['REQUEST_URI'],'/'));

// Instantiate a timer.
$app['timer'] = new SIDR\Timer();

// Catch errors:
$app->error(function (\Exception $e) use ($app) {

  $message = 'An error occured: ' . get_class($e) . ' ' . $e->getMessage();

  return $app['twig']->render('page-error.twig', array(
    'title' => 'Error',
    'content' => $message,
  ));
});

// Activates log of request / response for backend comms
$app['debug_backend_requests'] = !empty($settings['debug_backend_requests']);

// Passes cookie to backend to enable xdebug.
$app['xdebug_backend_requests'] = !empty($settings['xdebug_backend_requests']);

// Debug mode on/off:
$app['debug'] = !empty($settings['silex_debug']);

// Show the timer:
$app['show_timer'] = !empty($settings['show_timer']);