<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Nikic\IncludeInterceptor\Tests;

use Nikic\IncludeInterceptor\FileFilter;
use Nikic\IncludeInterceptor\Interceptor;
use PHPUnit\Framework\Error\Warning;

class InterceptorTests extends TestCase {
    public function testInterceptNoopHook() {
        $calledCode = '';
        $method = $this->loadWithHook('addOne.php', function ($path) use (&$calledCode) {
            $code = file_get_contents($path);
            $calledCode = $code;
            return $code;
        });
        $this->assertEquals(2, $method(1));
        $this->assertEquals($calledCode, file_get_contents(__DIR__ . '/data/addOne.php'));
    }

    public function testInterceptSingleHook() {
        $method = $this->loadWithHook('addOne.php', function ($path) {
            $code = file_get_contents($path);
            return str_replace('1', '2', $code);
        });
        $this->assertEquals(3, $method(1));
    }

    /**
     * @return callable
     */
    private function loadWithHook(string $file, callable $hook) {
        $source = __DIR__ . '/data/' . $file;
        $instance = new Interceptor($hook);
        $stream = $instance->intercept($source);
        return $this->loadCode($stream);
    }

    public function testIntercept() {
        $filter = FileFilter::createAllBlacklisted();
        $filter->addWhiteList(__DIR__ . '/data');
        $instance = new Interceptor(function (string $path) use ($filter) {
            if (!$filter->test($path)) return null;
            $code = file_get_contents($path);
            return str_replace('1', '2', $code);
        });
        $instance->setUp();

        /** @var callable $method */
        $method = include 'data/addOne.php';

        $instance->tearDown();

        $this->assertEquals(3, $method(1));
    }

    public function testPharIntercept() {
        $filter = FileFilter::createAllBlacklisted();
        $filter->addWhiteList('phar://' . __DIR__ . '/data.phar');
        $instance = new Interceptor(function (string $path) use ($filter) {
            if (!$filter->test($path)) return null;
            $code = file_get_contents($path);
            return str_replace('1', '2', $code);
        });
        $instance->setUp();

        /** @var callable $method */
        $method = include 'phar://' . __DIR__ . '/../tests/data.phar/./addOne.php';

        $instance->tearDown();

        $this->assertEquals(3, $method(1));
    }

    public function testNotExistingFile() {
        $instance = new Interceptor(function (string $path) {
            throw new \Exception('Should not be called!');
        });
        $instance->setUp();

        try {
            $this->expectException(Warning::class);
            include __DIR__ . '/data/doesntExist.php';
        } finally {
            $instance->tearDown();
        }
    }

    public function testDoubleSetup() {
        $this->expectException(\BadMethodCallException::class);
        $instance = new Interceptor(function(string $path) {
            return null;
        });

        $instance->setUp();
        try {
            $instance->setUp();
        } catch (\BadMethodCallException $e) {
            $instance->tearDown();
            throw $e;
        }
        $instance->tearDown();
    }

    public function testTearDownSetup() {
        $filter = FileFilter::createAllBlacklisted();
        $filter->addWhiteList(__DIR__ . '/data');
        $instance = new Interceptor(function (string $path) use ($filter) {
            if (!$filter->test($path)) return null;
            $code = file_get_contents($path);
            return str_replace('1', '2', $code);
        });

        $instance->setUp();

        /** @var callable $method1 */
        $method1 = include 'data/addOne.php';

        $instance->tearDown();

        /** @var callable $method2 */
        $method2 = include 'data/addOne.php';

        $instance->setUp();
        /** @var callable $method3 */
        $method3 = include 'data/addOne.php';

        $instance->tearDown();

        $this->assertEquals(3, $method1(1));
        $this->assertEquals(2, $method2(1));
        $this->assertEquals(3, $method3(1));
    }
}
