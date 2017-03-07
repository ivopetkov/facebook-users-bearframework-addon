<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('ivopetkov/facebook-users-bearframework-addon', __DIR__, [
    'require' => [
        'ivopetkov/users-bearframework-addon',
        'bearframework/localization-addon'
    ]
]);
