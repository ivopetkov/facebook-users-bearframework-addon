<?php

/*
 * Facebook login provider for the for the Bear Framework users addon
 * https://github.com/ivopetkov/facebook-users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class FacebookUsersTest extends BearFramework\AddonTests\PHPUnitTestCase
{
    /**
     * 
     */
    public function testOutput()
    {
        $app = $this->getApp();

        $this->assertTrue($app->users->getProvider('facebook') !== null);
    }
}
