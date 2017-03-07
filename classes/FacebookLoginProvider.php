<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
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
        if (_INTERNAL_IVOPETKOV_FACEBOOK_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
            return 'Вход с Facebook';
        } else {
            return 'Login with Facebook';
        }
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
        $response->redirectUrl = $app->urls->get('/-ivopetkov-facebook-user-redirect?referer=' . rawurlencode($locationUrl));
        return $response;
    }

    public function getUserProperties(string $id): array
    {
        $properties = [];
        $userData = $app->users->getUserData('facebook', $id);
        if (is_array($userData)) {
            $properties['name'] = $userData['name'];
            $properties['image'] = 'https://graph.facebook.com/v2.8/' . $userData['id'] . '/picture?width=1000';
            $$properties['url'] = 'https://facebook.com/' . $userData['id'] . '/';
        } else {
            $properties['name'] = 'Anonymous';
        }
//        if (_INTERNAL_IVOPETKOV_FACEBOOK_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
//            $user->description = 'Facebook профил';
//        } else {
//            $user->description = 'Facebook account';
//        }
        return $user;
    }

}
