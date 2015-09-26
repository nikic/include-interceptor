<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Interceptor\Tests;

use Icewind\Interceptor\Interceptor;

class InterceptorTests extends TestCase {
	public function whiteListProvider() {
		return [
			[['/foo'], '/foo/bar.php', true],
			[['/foo/'], '/foo/bar.php', true],
			[['/foo/bar'], '/foo/bar.php', false],
			[['/foobar'], '/foo/bar.php', false],
			[['/foo/'], '/foo/bar.phar', true],
			[['/foo/'], '/foo/bar.txt', false],
			[['/foo/'], '/foo/php', false]
		];
	}

	/**
	 * @param string[] $whiteList
	 * @param string $path
	 * @param bool $expected
	 * @dataProvider whiteListProvider
	 */
	public function testShouldIntercept($whiteList, $path, $expected) {
		$instance = new Interceptor();
		foreach ($whiteList as $folder) {
			$instance->addWhiteList($folder);
		}
		$this->assertEquals($expected, $instance->shouldIntercept($path));
	}

	public function testInterceptNoHooks() {
		$method = $this->loadWithHooks('addOne.php', []);
		$this->assertEquals(2, $method(1));
	}

	public function testInterceptNoopHook() {
		$calledCode = '';
		$method = $this->loadWithHooks('addOne.php', [function ($code) use (&$calledCode) {
			$calledCode = $code;
		}]);
		$this->assertEquals(2, $method(1));
		$this->assertEquals($calledCode, file_get_contents(__DIR__ . '/data/addOne.php'));
	}

	public function testInterceptSingleHook() {
		$method = $this->loadWithHooks('addOne.php', [function ($code) {
			return str_replace('1', '2', $code);
		}]);
		$this->assertEquals(3, $method(1));
	}

	public function testInterceptMultipleHooks() {
		$method = $this->loadWithHooks('addOne.php', [function ($code) {
			return str_replace('1', '2', $code);
		}, function ($code) {
			return str_replace('+', '-', $code);
		}]);
		$this->assertEquals(-1, $method(1));
	}

	/**
	 * @param string $file
	 * @param callable[] $hooks
	 * @return callable
	 * @throws \Exception
	 */
	private function loadWithHooks($file, array $hooks) {
		$source = __DIR__ . '/data/' . $file;
		$instance = new Interceptor();

		foreach ($hooks as $hook) {
			$instance->addHook($hook);
		}

		$stream = $instance->intercept($source);
		return $this->loadCode($stream);
	}

	public function testIntercept() {
		$instance = new Interceptor();
		$instance->addHook(function ($code) {
			return str_replace('1', '2', $code);
		});
		$instance->addWhiteList(__DIR__ . '/data');
		$instance->setUp();

		/** @var callable $method */
		$method = include 'data/addOne.php';

		$instance->unwrap();

		$this->assertEquals(3, $method(1));
	}
}
