<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Interceptor\Tests;

use Icewind\Interceptor\FileFilter;

class FileFilterTests extends TestCase {
	public function filterProvider() {
		return [
			['/foo.txt', [], [], [], false],
			['/foo.txt', [], [], ['txt'], false],
			['/foo.txt', [''], [], [], false],
			['/foo.txt', [''], [], [], false],
			['/foo.txt', [''], [], ['php'], false],
			['/foo.txt', [''], [], ['txt'], true],
			['/bar/asd/foo.txt', [''], ['/bar'], ['txt'], false],
			['/bar/asd/foo.txt', ['', '/bar/asd'], ['/bar'], ['txt'], true],
			['/bar/asd/foo.txt', ['/bar/asd'], ['/bar'], ['txt'], true],
			['/bar/asd/foo.txt', ['/bar/asd'], ['/bar/asd/foo'], ['txt'], true],
			['/bar/asd/foo.txt', ['/bar'], ['/bar'], ['txt'], false],
			['/bar/asd/foo.txt', ['/bar'], ['/bar/asd'], ['txt'], false],
		];
	}

	/**
	 * @param string $path
	 * @param string[] $whiteList
	 * @param string[] $blackList
	 * @param string[] $extensions
	 * @param bool $expected
	 * @dataProvider filterProvider
	 */
	public function testFilter($path, $whiteList, $blackList, $extensions, $expected) {
		$instance = new FileFilter();
		foreach ($whiteList as $dir) {
			$instance->addWhiteList($dir);
		}
		foreach ($blackList as $dir) {
			$instance->addBlackList($dir);
		}
		foreach ($extensions as $extension) {
			$instance->addExtension($extension);
		}
		$this->assertEquals($expected, $instance->test($path));
	}
}
