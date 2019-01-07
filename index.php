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
$context = $app->context->get(__FILE__);

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

$getAddonOptions = function() use ($app) {
    $addonOptions = $app->addons->get('ivopetkov/facebook-users-bearframework-addon')->options;
    if (!isset($addonOptions['facebookAppID'])) {
        throw new \Exception('The ivopetkov/facebook-users-bearframework-addon facebookAppID option is required');
    }
    if (!isset($addonOptions['facebookAppSecret'])) {
        throw new \Exception('The ivopetkov/facebook-users-bearframework-addon facebookAppSecret option is required');
    }
    if (!isset($addonOptions['oauthRedirectUrl'])) {
        throw new \Exception('The ivopetkov/facebook-users-bearframework-addon oauthRedirectUrl option is required');
    }
    return $addonOptions;
};

$app->routes
        ->add('*', function() use ($app, $getAddonOptions) {
            $addonOptions = $getAddonOptions();
            if ($app->request->base . $app->request->path === $addonOptions['oauthRedirectUrl']) {
                return FacebookLoginProvider::handleOAuthRedirect();
            }
        })
        ->add('/-ivopetkov-facebook-user-redirect', function() use ($app, $getAddonOptions) {
            $addonOptions = $getAddonOptions();
            $referer = (string) $app->request->query->getValue('referer');
            $url = 'https://www.facebook.com/v3.2/dialog/oauth?client_id=' . $addonOptions['facebookAppID'] . '&redirect_uri=' . urlencode($addonOptions['oauthRedirectUrl']) . '&state=' . base64_encode($referer);
            $response = new App\Response\TemporaryRedirect($url);
            $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
            return $response;
        });
