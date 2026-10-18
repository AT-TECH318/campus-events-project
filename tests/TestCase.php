<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Each HTTP call in a test shares one application instance.
     * Sanctum caches the authenticated user on that instance, so clear it
     * before the next request. A real PHP request starts with an empty guard.
     *
     * @param  array<string, string>  $server
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
