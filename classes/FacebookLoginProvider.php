<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use IvoPetkov\BearFrameworkAddons\Users\ILoginProvider;
use BearFramework\App;

class FacebookLoginProvider implements ILoginProvider
{

    public function hasLoginButton(): bool
    {
        return true;
    }

    public function getLoginButtonText(): string
    {
        return __('ivopetkov.users.facebook.loginWithFacebook');
    }

    public function hasLogoutButton(): bool
    {
        return true;
    }

    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $response = new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
        $app = App::get();
        $locationUrl = strlen($context->locationUrl) > 0 ? $context->locationUrl : $app->urls->get('/');
        $response->redirectUrl = $app->urls->get('/-ivopetkov-facebook-user-redirect?referer=' . rawurlencode($locationUrl), false);
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

}
