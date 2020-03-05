<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\FacebookLoginProvider;

$app = App::get();
$context = $app->contexts->get(__DIR__);

$context->classes
        ->add(FacebookLoginProvider::class, 'classes/FacebookLoginProvider.php');

$app->users
        ->addProvider('facebook', FacebookLoginProvider::class);

$app->localization
        ->addDictionary('en', function() use ($context) {
            return include $context->dir . '/locales/en.php';
        })
        ->addDictionary('bg', function() use ($context) {
            return include $context->dir . '/locales/bg.php';
        })
        ->addDictionary('ru', function() use ($context) {
            return include $context->dir . '/locales/ru.php';
        });

