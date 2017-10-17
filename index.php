<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
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
    return $addonOptions;
};

$app->routes
        ->add('/-ivopetkov-facebook-user-redirect', function() use ($app, $getAddonOptions) {
            $addonOptions = $getAddonOptions();
            $referer = (string) $app->request->query->getValue('referer');
            $redirectUri = $app->urls->get('/-ivopetkov-facebook-user-auth?referer=' . rawurlencode($referer), false);
            $url = 'https://www.facebook.com/v2.8/dialog/oauth?client_id=' . $addonOptions['facebookAppID'] . '&redirect_uri=' . urlencode($redirectUri);
            $response = new App\Response\TemporaryRedirect($url);
            return $response;
        })
        ->add('/-ivopetkov-facebook-user-auth', function() use ($app, $getAddonOptions) {
            $referer = (string) $app->request->query->getValue('referer');
            $code = (string) $app->request->query->getValue('code');

            $addonOptions = $getAddonOptions();

            $makeRequest = function($url) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $url,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36'
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                return $response;
            };

            $redirectUri = $app->urls->get('/-ivopetkov-facebook-user-auth?referer=' . rawurlencode($referer), false);

            $tokenUrl = 'https://graph.facebook.com/v2.8/oauth/access_token?client_id=' . $addonOptions['facebookAppID'] . '&redirect_uri=' . urlencode($redirectUri) . '&client_secret=' . $addonOptions['facebookAppSecret'] . '&code=' . rawurlencode($code);
            $response = $makeRequest($tokenUrl);
            $parts = json_decode($response, true);
            if (isset($parts['access_token'])) {
                $accessToken = $parts['access_token'];
                $userData = json_decode($makeRequest("https://graph.facebook.com/v2.8/me?access_token=" . urlencode($accessToken)), true);
                if (isset($userData['id'], $userData['name'])) {
                    $id = $userData['id']; //md5('facebook' . $userData['id']);
                    $app->users->saveUserData('facebook', $id, [
                        'id' => $id,
                        'name' => $userData['name']
                    ]);
                    $app->currentUser->login('facebook', $id);
                }
            }
            $response = new App\Response\TemporaryRedirect($referer);
            return $response;
        });
