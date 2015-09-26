<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Interceptor\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase {
	/**
	 * @param resource $stream
	 * @return callable
	 * @throws \Exception
	 */
	protected function loadCode($stream) {
		$id = uniqid();
		$file = tempnam(sys_get_temp_dir(), $id . '.php');
		file_put_contents($file, $stream);
		try {
			$result = include $file;
			unlink($file);
			return $result;
		} catch (\Exception $e) {
			unlink($file);
			throw $e;
		}
	}
}
