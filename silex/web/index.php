<?php

/**
 * @file index.php
 * This file is given as an example: it's yours to edit as you see fit.
 * You'll no doubt need to add your own routes in here for your application to
 * work. Existing routes may also need to be changed from SIDR's classes to your
 * own classes that override SIDR's funtionality.
 **/

include '../bootstrap.php';

$app->get('/', 'SIDR\PageController::viewFrontPage');

$app->get('search', 'SIDR\SearchController::search');

$app->get('/register',   'SIDR\UserController::registerForm');
$app->post('/register',  'SIDR\UserController::register');

$app->get('/login',   'SIDR\UserController::login');
$app->post('/login',  'SIDR\UserController::authenticate');

$app->get('/logout',  'SIDR\UserController::logout');

$app->get('/user', 'SIDR\UserController::viewAccount');
$app->post('/user', 'SIDR\UserController::updateAccount');

$app->get('/user/{user_id}/', 'SIDR\ProfileController::viewAccount');

$app->get('/{content_type}/add', 'SIDR\NodeController::add');
$app->post('/{content_type}/add', 'SIDR\NodeController::addSubmit');

$app->get('/{content_type}/{node_id}/delete', 'SIDR\NodeController::delete');
$app->post('/{content_type}/{node_id}/delete', 'SIDR\NodeController::deleteSubmit');

/**
 * Add routing for views automatically by asking the back end what it has.
 */
#$views = $app['memcache']->get('sidr/views');
#
#if (!$views) {
#  $response = $app['drupal']->get('sidr/views', array('prefix_endpoint' => FALSE));
#  $views = $response->json();
#  $views = $app['memcache']->set('sidr/views', $views);
#}

// Pass this info to the Views Controller so it can use it later.
#SIDR\ViewsController::setViewsInfo($views);

#foreach ($views as $view) {
#  $app->get($view['path'], 'SIDR\ViewsController::view');
#}


/**
 * Default route to load nodes by arbitrary path.
 * This route must be the last one declared for it to work.
 */
$app->match('{url}', 'SIDR\NodeController::view')->assert('url', '.+');

$app->run();
