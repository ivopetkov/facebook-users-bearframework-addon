<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use IvoPetkov\BearFrameworkAddons\Users\LoginProvider;
use BearFramework\App;

class FacebookLoginProvider extends LoginProvider
{

    static private $config = null;

    public function __construct()
    {
        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.facebook.loginWithFacebook');
        $this->hasLogout = true;
    }

    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $response = new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
        $app = App::get();
        $locationUrl = strlen($context->locationUrl) > 0 ? $context->locationUrl : $app->urls->get('/');
        $response->redirectUrl = $app->urls->get('/-ivopetkov-facebook-user-redirect') . '?referer=' . rawurlencode($locationUrl);
        return $response;
    }

    public function getUserProperties(string $id): array
    {
        $app = App::get();
        $properties = [];
        $userData = $app->users->getUserData('facebook', $id);
        if (is_array($userData)) {
            $properties['name'] = $userData['name'];
            $properties['image'] = 'https://graph.facebook.com/v3.2/' . $userData['id'] . '/picture?width=1000';
            $properties['url'] = 'https://facebook.com/' . $userData['id'] . '/';
        } else {
            $properties['name'] = 'Anonymous';
        }
        $properties['description'] = __('ivopetkov.users.facebook.description');
        return $properties;
    }

    /**
     * 
     * @param array $config
     */
    static function initialize(array $config)
    {
        $app = App::get();
        if (!isset($config['facebookAppID'])) {
            throw new \Exception('The facebookAppID config variable is required!');
        }
        if (!isset($config['facebookAppSecret'])) {
            throw new \Exception('The facebookAppSecret config variable is required!');
        }
        if (!isset($config['oauthRedirectUrl'])) {
            throw new \Exception('The oauthRedirectUrl config variable is required!');
        }
        self::$config = $config;

        $app->routes
                ->add('*', function() use ($app, $config) {
                    if ($app->request->base . $app->request->path === $config['oauthRedirectUrl']) {
                        return FacebookLoginProvider::handleOAuthRedirect();
                    }
                })
                ->add('/-ivopetkov-facebook-user-redirect', function() use ($app, $config) {
                    $referer = (string) $app->request->query->getValue('referer');
                    $url = 'https://www.facebook.com/v3.2/dialog/oauth?client_id=' . $config['facebookAppID'] . '&redirect_uri=' . urlencode($config['oauthRedirectUrl']) . '&state=' . base64_encode($referer);
                    $response = new App\Response\TemporaryRedirect($url);
                    $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
                    return $response;
                });
    }

    /**
     * 
     * @return \BearFramework\App\Response|null
     */
    static function handleOAuthRedirect()
    {
        $app = App::get();
        $referer = base64_decode((string) $app->request->query->getValue('state'));
        if (!is_string($referer)) {
            return null;
        }
        $code = (string) $app->request->query->getValue('code');

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

        $tokenUrl = 'https://graph.facebook.com/v3.2/oauth/access_token?client_id=' . self::$config['facebookAppID'] . '&redirect_uri=' . urlencode(self::$config['oauthRedirectUrl']) . '&client_secret=' . self::$config['facebookAppSecret'] . '&code=' . rawurlencode($code);
        $response = $makeRequest($tokenUrl);
        $parts = json_decode($response, true);
        if (isset($parts['access_token'])) {
            $accessToken = $parts['access_token'];
            $userData = json_decode($makeRequest("https://graph.facebook.com/v3.2/me?access_token=" . urlencode($accessToken)), true);
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

}
