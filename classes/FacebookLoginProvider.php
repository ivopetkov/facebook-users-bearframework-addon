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

    public function getLoginButtonText(): string
    {
        if (_INTERNAL_IVOPETKOV_FACEBOOK_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
            return 'Вход с Facebook';
        } else {
            return 'Login with Facebook';
        }
    }

    public function getDescriptionHTML(): string
    {
        if (_INTERNAL_IVOPETKOV_FACEBOOK_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
            return 'Facebook профил';
        } else {
            return 'Facebook account';
        }
    }

    public function hasLogout(): bool
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

    public function makeUser(string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $app = App::get();
        $userData = $app->users->getUserData('facebook', $id);
        $user = $app->users->make();
        $user->provider = 'facebook';
        $user->id = $id;
        if (is_array($userData)) {
            $user->name = $userData['name'];
            $user->image = 'https://graph.facebook.com/v2.8/' . $userData['id'] . '/picture?width=1000';
            $user->url = 'https://facebook.com/' . $userData['id'] . '/';
        } else {
            $user->name = 'Anonymous';
        }
        return $user;
    }

}
