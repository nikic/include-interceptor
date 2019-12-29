<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Nikic\IncludeInterceptor\Tests;

use Nikic\IncludeInterceptor\FileFilter;
use Nikic\IncludeInterceptor\Interceptor;

class IncludePathTests extends TestCase {

    public function testInterceptFromOtherFolder() {
        $filter = FileFilter::createDefault();
        $filter->addWhiteList(dirname(__DIR__) . '/data');
        $instance = new Interceptor(function(string $path) use ($filter) {
            if (!$filter->test($path)) return null;
            $code = file_get_contents($path);
            return str_replace('1', '2', $code);
        });
        $instance->setUp();

        /** @var callable $method */
        $method = include '../data/addOne.php';

        // Make sure a normal file_get_contents() works as well.
        $this->assertNotFalse(file_get_contents('../data/addOne.php'));

        $instance->tearDown();

        $this->assertEquals(3, $method(1));
    }
}
