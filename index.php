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
                $referer = base64_decode((string) $app->request->query->getValue('state'));
                if (!is_string($referer)) {
                    return;
                }
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

                $tokenUrl = 'https://graph.facebook.com/v2.8/oauth/access_token?client_id=' . $addonOptions['facebookAppID'] . '&redirect_uri=' . urlencode($addonOptions['oauthRedirectUrl']) . '&client_secret=' . $addonOptions['facebookAppSecret'] . '&code=' . rawurlencode($code);
                $response = $makeRequest($tokenUrl);
                $parts = json_decode($response, true);
                if (isset($parts['access_token'])) {
                    $accessToken = $parts['access_token'];
                    $userData = json_decode($makeRequest("https://graph.facebook.com/v2.8/me?access_token=" . urlencode($accessToken)), true);
                    if (isset($userData['id'], $userData['name'])) {
                        $id = $userData['id'];
                        $app->users->saveUserData('facebook', $id, [
                            'id' => $id,
                            'name' => $userData['name']
                        ]);
                        $app->currentUser->login('facebook', $id);
                    }
                }
                $response = new App\Response\TemporaryRedirect($referer);
                $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
                return $response;
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
