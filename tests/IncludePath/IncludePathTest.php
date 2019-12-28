<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Nikic\IncludeInterceptor\Tests;

use Nikic\IncludeInterceptor\Interceptor;

class IncludePathTests extends TestCase {

	public function testInterceptFromOtherFolder() {
		$instance = new Interceptor();
		$instance->addHook(function ($code) {
			return str_replace('1', '2', $code);
		});
		$instance->addWhiteList(dirname(__DIR__) . '/data');
		$instance->setUp();

		/** @var callable $method */
		$method = include '../data/addOne.php';

		$instance->tearDown();

		$this->assertEquals(3, $method(1));
	}
}
